<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\RegisterBonus;

class RegisterBonusController extends Controller
{
    /**
     * 回傳會員註冊優惠
     *
     * @Route("/user/{userId}/register_bonus",
     *        name = "api_get_user_register_bonus",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 會員id
     * @return JsonResponse
     */
    public function getUserRegisterBonusAction($userId)
    {
        $em = $this->getEntityManager();
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150010029);
        }

        $registerBonus = $em->find('BBDurianBundle:RegisterBonus', $userId);

        // 如果沒有註冊優惠設定，則往父層搜尋
        while (!$registerBonus && $user->hasParent()) {
            $user = $user->getParent();
            $registerBonus = $em->find('BBDurianBundle:RegisterBonus', $user->getId());
        }

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = [];

        if ($registerBonus) {
            $output['ret'] = $registerBonus->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳所有註冊優惠幣別限額
     *
     * @Route("/currency/register_bonus",
     *        name = "api_get_all_currency_register_bonus",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getAllCurrencyRegisterBonusAction()
    {
        $currencyOperator = $this->get('durian.currency');

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $currencyOperator->getAllRegisterBonus();

        return new JsonResponse($output);
    }

    /**
     * 設定新註冊會員優惠相關設定
     *
     * @Route("/user/{userId}/register_bonus",
     *        name = "api_set_user_register_bonus",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 會員id
     * @return JsonResponse
     */
    public function setUserRegisterBonusAction(Request $request, $userId)
    {
        $post = $request->request;
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $user = $em->find('BBDurianBundle:User', $userId);

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('register_bonus', ['userid' => $userId]);

        $amount = $post->get('amount');
        $multiply = $post->get('multiply');
        $commision = (bool) $post->get('refund_commision');

        // 金額要是整數
        if ($post->has('amount') && !$validator->isInt($amount, true)) {
            throw new \InvalidArgumentException('Amount must be an integer', 150410002);
        }

        if ($post->has('multiply') && !$validator->isInt($multiply, true)) {
            throw new \InvalidArgumentException('Multiply must be an integer', 150410003);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if (!$user) {
                throw new \RuntimeException('No such user', 150010029);
            }

            $currency = $user->getCurrency();
            $amountMax = $currencyOperator->getRegiterBonus($currency);

            // 當$amountMax為null，代表不支援此幣別
            if (is_null($amountMax)) {
                throw new \InvalidArgumentException('Register bonus not support this currency', 150410001);
            }

            $registerBonus = $em->find('BBDurianBundle:RegisterBonus', $userId);

            // 如果沒有紀錄就新增，有紀錄則修改
            if (!$registerBonus) {
                $registerBonus = new RegisterBonus($user);
                $em->persist($registerBonus);
            }

            // 設定金額
            $oldAmount = $registerBonus->getAmount();
            if ($post->has('amount') && $oldAmount != $amount) {
                // 金額範圍不得超過限額或限額為0時
                if ($amount > $amountMax) {
                    throw new \InvalidArgumentException('Amount exceeds the MAX amount', 150410004);
                }

                $log->addMessage('amount', var_export($oldAmount, true), var_export($amount, true));
                $registerBonus->setAmount($amount);
            }

            // 設定打碼倍倍數
            $oldMultiply = $registerBonus->getMultiply();
            if ($post->has('multiply') && $oldMultiply != $multiply) {
                $log->addMessage('multiply', var_export($oldMultiply, true), var_export($multiply, true));
                $registerBonus->setMultiply($multiply);
            }

            // 設定寫入退傭費用
            $oldCommision = $registerBonus->isRefundCommision();
            if ($post->has('refund_commision') && $oldCommision != $commision) {
                $log->addMessage('refund_commision', var_export($oldCommision, true), var_export($commision, true));
                $registerBonus->setRefundCommision($commision);
            }

            $operationLogger->save($log);
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output = [];
            $output['result'] = 'ok';
            $output['ret'] = $registerBonus->toArray();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 移除會員註冊優惠
     *
     * @Route("/user/{userId}/register_bonus",
     *        name = "api_remove_user_register_bonus",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $userId 會員id
     * @return JsonResponse
     */
    public function removeUserRegisterBonusAction($userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150410005);
        }

        $registerBonus = $em->find('BBDurianBundle:RegisterBonus', $userId);

        if (!$registerBonus) {
            throw new \RuntimeException('No RegisterBonus found', 150410006);
        }

        $log = $operationLogger->create('register_bonus', ['user_id' => $userId]);
        $operationLogger->save($log);

        $em->remove($registerBonus);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 回傳廳下註冊優惠資料
     *
     * @Route("/domain/{domain}/register_bonus",
     *        name = "api_get_register_bonus_by_domain",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integet $domain　廳主id
     * @return JsonResponse
     */
    public function getRegisterBonusByDomainAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('BBDurianBundle:RegisterBonus');

        $query = $request->query;
        $role = $query->get('role');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator = $this->get('durian.validator');
        $validator->validatePagination($firstResult, $maxResults);

        if (!$role) {
            throw new \InvalidArgumentException('No role specified', 150410008);
        }

        $user = $em->find('BBDurianBundle:User', $domain);

        if (!$user) {
            throw new \RuntimeException('No such user', 150410005);
        }

        if (!is_null($user->getParent())) {
            throw new \RuntimeException('Not a domain', 150410007);
        }

        $limit = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $total = $repository->countByDomain($domain, $role);
        $rets = $repository->getByDomain($domain, $role, $limit);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($rets as $ret) {
            $output['ret'][] = $ret->toArray();
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
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
