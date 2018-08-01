<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class UserCreatedPerIpController extends Controller
{

    /**
     * 取得新增使用者IP統計資訊
     *
     * @Route("/user/created_per_ip",
     *        name = "api_get_user_created_per_ip",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');

        $em = $this->getDoctrine()->getManager('share');
        $repo = $em->getRepository('BBDurianBundle:UserCreatedPerIp');

        $query = $request->query;

        $firstResult   = $query->get('first_result');
        $maxResults    = $query->get('max_results');

        $domain        = $query->get('domain');
        $ip            = $query->get('ip');
        $sort          = $query->get('sort');
        $order         = $query->get('order');
        $count         = $query->get('count');
        $startTime     = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $endTime       = $parameterHandler->datetimeToYmdHis($query->get('end'));
        $orderBy       = $parameterHandler->orderBy($sort, $order);
        $output['ret'] = array();
        $criteria      = array();

        if ($domain !== null) {
            $criteria['domain'] = $domain;
        }

        if ($ip !== null) {
            $criteria['ip'] = ip2long($ip);
        }

        if ($startTime !== null) {
            $start = new \DateTime($startTime);
            $criteria['startTime'] = $start->format('YmdH\0000');
        }

        if ($endTime !== null) {
            $end = new \DateTime($endTime);
            $criteria['endTime'] = $end->format('YmdH\0000');
        }

        if ($count !== null) {
            $criteria['count'] = $count;
        }

        $perIps = $repo->getUserCreatedPerIp($criteria, $firstResult, $maxResults, $orderBy);

        foreach ($perIps as $perIp) {
            $output['ret'][] = $perIp->toArray();
        }

        $total = $repo->countUserCreatedPerIp($criteria);
        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }
}
