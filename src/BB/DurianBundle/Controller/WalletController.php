<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class WalletController extends Controller
{
    /**
     * 回傳使用者支援的交易方式
     *
     * @Route("/wallet/payway",
     *          name = "api_wallet_get_payway",
     *          requirements = {"_format" = "json"},
     *          defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $em = $this->getEntityManager();

        $query = $request->query;
        $userId = $query->get('user_id');

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 70033);
        }

        $repo = $em->getRepository('BBDurianBundle:UserPayway');
        $payway = $repo->getUserPayway($user);

        if (!$payway) {
            throw new \RuntimeException('No userPayway found', 70027);
        }

        $out['result'] = 'ok';
        $out['ret'] = $payway->toArray();

        // 回傳的 user_id 有可能是上層的編號，故必須覆蓋
        $out['ret']['user_id'] = $userId;

        return new JsonResponse($out);
    }

    /**
     * 取得使用者存提款紀錄
     *
     * @Route("/wallet/deposit_withdraw",
     *        name = "api_get_deposit_withdraw",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function getDepositWithdrawAction(Request $request)
    {
        $query = $request->query;
        $userId = $query->get('user_id');

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150070035);
        }

        $em = $this->getEntityManager();
        $depositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', $userId);

        if (!$depositWithdraw) {
            throw new \RuntimeException('No deposit withdraw found', 150070034);
        }

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $depositWithdraw->toArray();

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
