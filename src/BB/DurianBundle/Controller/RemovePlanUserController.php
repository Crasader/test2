<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class RemovePlanUserController extends Controller
{
    /**
     * 更新刪除使用者名單狀態
     *
     * @Route("/remove_plan_user/{planUserId}",
     *        name = "api_update_remove_plan_user",
     *        requirements = {"planUserId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $planUserId 使用者編號
     * @return JsonResponse
     */
    public function updatePlanUserStatusAction(Request $request, $planUserId)
    {
        $request = $request->request;
        $validator = $this->get('durian.validator');

        $remove = (bool) $request->get('remove', 0);
        $cancel = (bool) $request->get('cancel', 0);
        $recoverFail = (bool) $request->get('recover_fail', 0);
        $getBalanceFail = (bool) $request->get('get_balance_fail', 0);
        $errorCode = $request->get('error_code', null);
        $memo = $request->get('memo', '');

        if (!$remove && !$cancel && !$recoverFail && !$getBalanceFail) {
            throw new \InvalidArgumentException('No removePlanUser status specified', 150770001);
        }

        // 驗證參數編碼是否為utf8
        $validator->validateEncode($memo);

        $emShare = $this->getEntityManager('share');

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', $planUserId);

        if (!$rpUser) {
            throw new \RuntimeException('No removePlanUser found', 150770002);
        }

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('remove_plan_user', ['id' => $planUserId]);

        if ($remove && !$rpUser->isRemove()) {
            $rpUser->remove();
            $log->addMessage('remove', 'false', 'true');
        }

        if ($cancel && !$rpUser->isCancel()) {
            $rpUser->cancel();
            $log->addMessage('cancel', 'false', 'true');
        }

        if ($recoverFail && !$rpUser->isRecoverFail()) {
            $rpUser->recoverFail();
            $log->addMessage('recoverFail', 'false', 'true');
        }

        if ($getBalanceFail && !$rpUser->isGetBalanceFail()) {
            $rpUser->getBalanceFail();
            $log->addMessage('getBalanceFail', 'false', 'true');
        }

        if ($errorCode) {
            $rpUser->setErrorCode($errorCode);
            $log->addMessage('error_code', $errorCode);
        }

        if ($memo) {
            $rpUser->setMemo($memo);
            $log->addMessage('memo', $memo);
        }

        if ($log->getMessage()) {
            $now = new \Datetime();
            $rpUser->setModifiedAt($now);

            $log->addMessage('modifiedAt', $now->format('Y-m-d H:i:s'));
            $operationLogger->save($log);
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $rpUser->toArray();

        return new JsonResponse($output);
    }

    /**
     * 檢查刪除名單的使用者是否符合刪除條件
     *
     * @Route("/remove_plan_user/{planUserId}/check",
     *        name = "api_check_remove_plan_user",
     *        requirements = {"planUserId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $planUserId 使用者編號
     * @return JsonResponse
     */
    public function checkPlanUserAction($planUserId)
    {
        $emShare = $this->getEntityManager('share');
        $em = $this->getEntityManager();
        $rpu = $emShare->find('BBDurianBundle:RmPlanUser', $planUserId);
        $userId = $rpu->getUserId();
        $user = $em->find('BBDurianBundle:User', $userId);

        $output = [];
        $output['result'] = 'ok';

        if (!$user) {
            $output['ret'][] = "使用者不存在";

            return new JsonResponse($output);
        }

        $lastLogin = $user->getLastLogin();
        if (!is_null($lastLogin)) {
            $diffDays = date_diff($lastLogin, new \DateTime('now'))->format('%a');

            if ($diffDays < 60) {
                $output['ret'][] = '使用者最近兩個月有登入記錄';

                return new JsonResponse($output);
            }
        }

        if ($user->getCash()) {
            $userHasDepositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', $userId);
            if ($userHasDepositWithdraw) {
                $output['ret'][] = '使用者有出入款記錄';

                return new JsonResponse($output);
            }
        }

        if ($user->getCashFake()) {
            $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', $userId);

            if ($userHasApiTransferInOut) {
                $output['ret'][] = '使用者有api轉入轉出記錄';

                return new JsonResponse($output);
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}
