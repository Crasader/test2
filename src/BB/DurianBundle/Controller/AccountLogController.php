<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\AccountLog;

class AccountLogController extends Controller
{
    /**
     * AccountLog所有狀態陣列
     *
     * @var array
     */
    private $statusArray = array(
        AccountLog::UNTREATED,
        AccountLog::SENT,
        AccountLog::CANCEL
    );

    /**
     * 取得到帳戶系統參數紀錄列表
     *
     * @Route("/account_log/list",
     *        name = "api_account_log_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function accountLogListAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:AccountLog');
        $validator = $this->get('durian.validator');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $status = $query->get('status');
        $count = $query->get('count');
        $web = $query->get('web');

        $validator->validatePagination($firstResult, $maxResults);

        $output['ret'] = $criteria = array();

        if ($status !== null) {
            $criteria['status'] = $status;
        }

        if ($count !== null) {
            $criteria['count'] = $count;
        }

        if (trim($web) !== '') {
            $criteria['web'] = $web;
        }

        $accountLogs = $repo->getAccountLog($criteria, $firstResult, $maxResults);

        foreach ($accountLogs as $accountLog) {
            $output['ret'][] = $accountLog->toArray();
        }

        $total = $repo->countAccountLog($criteria);
        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 歸零到帳戶系統發送次數
     *
     * @Route("/account_log/{id}/zero",
     *        name = "api_account_log_zero",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function zeroAccCountAction($id)
    {
        $em = $this->getEntityManager();

        $output = array();

        $accountLog = $em->find('BBDurianBundle:AccountLog', $id);

        if (!$accountLog) {
            throw new \RuntimeException('No such account log', 160001);
        }

        $accountLog->zeroCount();

        $em->flush();

        $output['ret'] = $accountLog->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定到帳戶系統紀錄狀態
     *
     * @Route("/account_log/{id}/status",
     *        name = "api_account_log_status",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function setStatusAction(Request $request, $id)
    {
        $em = $this->getEntityManager();

        $status = $request->get('status');

        $output = array();

        if (is_null($status) || $status === '') {
            throw new \InvalidArgumentException('No status specified', 160002);
        }

        if (!in_array($status, $this->statusArray)) {
            throw new \InvalidArgumentException('No status specified', 160002);
        }

        $accountLog = $em->find('BBDurianBundle:AccountLog', $id);

        if (!$accountLog) {
            throw new \RuntimeException('No such account log', 160001);
        }

        $accountLog->setStatus($status);
        $output['debug'] = $status;
        $em->flush();

        $output['ret'] = $accountLog->toArray();
        $output['result'] = 'ok';

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
