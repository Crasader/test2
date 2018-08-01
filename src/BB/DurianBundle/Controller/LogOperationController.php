<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LogOperationController extends Controller
{
    /*
     * 操作紀錄分頁顯示數量
     */
    private $maxResults = 100;

    /**
     * 取得操作紀錄
     *
     * @Route("/log_operation",
     *        name = "log_operation")
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     *
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Renders,Response
     */
    public function getLogOperationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager('share');

        //若收到ajax的request則回傳操作紀錄
        if ($request->isXmlHttpRequest()) {
            $logOperation = $this->getLogOperation($request);

            return new JsonResponse($logOperation);
        }

        $repository = $em->getRepository('BBDurianBundle:LogOperation');

        $tables = $repository->getTableNameInLogOperation();

        return $this->render(
            "BBDurianBundle:Default/LogOperation:logOperation.html.twig",
            array(
                'maxResults' => $this->maxResults,
                'tables'     => $tables
            )
        );
    }

    /**
     * 取得操作紀錄
     *
     * @param Request $request
     * @return array
     */
    private function getLogOperation(Request $request)
    {
        $em = $this->getDoctrine()->getManager('share');
        $query = $request->query;

        $firstResult = 0;
        $criteria = array();

        $tableName = $query->get('table_name');
        $majorKey = $query->get('major_key');
        $startAt = $query->get('start_at');
        $endAt = $query->get('end_at');
        $uri = $query->get('uri');
        $method = $query->get('method');
        $serverName = $query->get('server_name');
        $clientIp = $query->get('client_ip');
        $message = $query->get('message');

        if (trim($tableName) != '') {
            $criteria['tableName'] = $tableName;
        }

        if (trim($majorKey) != '') {
            $criteria['majorKey'] = $majorKey;
        }

        if (trim($startAt) != '') {
            $criteria['startAt'] = $startAt;
        }

        if (trim($endAt) != '') {
            $criteria['endAt'] = $endAt;
        }

        if (trim($uri) != '') {
            $criteria['uri'] = $uri;
        }

        if (!empty($method)) {
            $criteria['method'] = $method;
        }

        if (trim($serverName) != '') {
            $criteria['serverName'] = $serverName;
        }

        if (trim($clientIp) != '') {
            $criteria['clientIp'] = $clientIp;
        }

        if (trim($message) != '') {
            $criteria['message'] = $message;
        }

        if ($query->has('page')) {
            $firstResult = ($query->get('page') - 1) * $this->maxResults;
        }

        $repository = $em->getRepository('BBDurianBundle:LogOperation');
        $logOperation = $repository->getLogOperation(
            $criteria,
            $firstResult,
            $this->maxResults
        );

        foreach ($logOperation as $key => $value) {
            $logOperation[$key]['at'] = $value['at']->format(\DateTime::ISO8601);
        }

        $num = $repository->countLogOperation($criteria);

        $logOperation['page'] = ceil($num / $this->maxResults);

        return $logOperation;
    }
}
