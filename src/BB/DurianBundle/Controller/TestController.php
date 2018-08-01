<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Test;
use Symfony\Component\HttpFoundation\Request;

/**
 * 測試用
 */
class TestController extends Controller
{
    /**
     * 空白action
     *
     * @Route("/test", name = "test")
     *
     * @return Response
     */
    public function testAction()
    {
        return new Response('It works!');
    }

    /**
     * 指定延遲秒數回傳系統資訊
     *
     * @Route("/api/test/timeout", name= "api_test_timeout",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function timeoutAction(Request $request)
    {
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $second  = $query->get('second', 5);

        if (!$validator->isInt($second, true)) {
            $second = 5;
        }

        sleep($second);

        $info = $_SERVER;
        unset($info["DOCUMENT_ROOT"]);
        unset($info["SCRIPT_FILENAME"]);
        unset($info["HOME"]);

        $output['ret'] = $info;

        return new JsonResponse($output);
    }

    /**
     * 測試資料庫與Redis連線是否正常
     *
     * @Route("/api/test/connection",
     *        name= "api_test_connection",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @return JsonResponse
     */
    public function connectionAction()
    {
        $output['result'] = 'ok';
        $output = array_merge_recursive($output, $this->connectDb());
        $output = array_merge_recursive($output, $this->connectRedis());
        if (key_exists('mysql_error', $output) || key_exists('redis_error', $output) ) {
            $output['result'] = 'error';
        }

        return new JsonResponse($output);
    }

    /**
     * 測試資料庫連線
     *
     * @return array
     */
    private function connectDb()
    {
        $output = [];
        try {
            $em = $this->getDoctrine()->getManager('default');

            $testData = new Test('TEST!!');
            $em->persist($testData);
            $em->flush();

            $id = $testData->getId();
            $data = $em->find('BBDurianBundle:Test', $id);

            if (!$data) {
                $output['mysql_error'][] = 'Database can not insert data';

                return $output;
            }

            $data->setMemo('RESET');
            $em->persist($data);
            $em->flush();

            $alteredData = $em->find('BBDurianBundle:Test', $id);
            if ($alteredData->getMemo() != 'RESET') {
                $output['mysql_error'][] = 'Database can not update data';

                return $output;
            }

            $em->remove($alteredData);
            $em->flush();
            $data = $em->find('BBDurianBundle:Test', $id);

            if ($data) {
                $output['mysql_error'][] = 'Database can not delete data';

                return $output;
            }
        } catch (\Exception $e) {
            $output['mysql_error'][] = $e->getMessage();
        }

        return $output;
    }

    /**
     * 測試redis連線
     *
     * @return array
     */
    private function connectRedis()
    {
        $output = [];
        try {
            $redis = $this->get('snc_redis.default_client');
            $redis->del('foo');
            $redis->set('foo', 'bar');

            if ($redis->get('foo') != 'bar') {
                $output['redis_error'][] = 'Redis can not set key';

                return $output;
            }

            $redis->del('foo');

            if ($redis->get('foo')) {
                $output['redis_error'][] = 'Redis can not delete key';

                return $output;
            }
        } catch (\Exception $e) {
            $output['redis_error'][] = $e->getMessage();
        }

        return $output;
    }

    /**
     * 檢測資料庫
     *
     * @Route("/api/test/checkdb", name= "api_test_checkdb",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkDbAction(Request $request)
    {
        $query = $request->query;

        $redis = $query->get('redis');
        $mysql = $query->get('mysql');

        $output = [];
        $output['result'] = 'ok';

        // 所有redis名稱
        $redisClient = [
            'default'       => 'snc_redis.default',
            'sequence'      => 'snc_redis.sequence',
            'cluster'       => 'snc_redis.cluster',
            'map'           => 'snc_redis.map',
            'reward'        => 'snc_redis.reward',
            'wallet1'       => 'snc_redis.wallet1',
            'wallet2'       => 'snc_redis.wallet2',
            'wallet3'       => 'snc_redis.wallet3',
            'wallet4'       => 'snc_redis.wallet4',
            'kue'           => 'snc_redis.kue',
            'slide'         => 'snc_redis.slide',
            'oauth2'        => 'snc_redis.oauth2',
            'bodog'         => 'snc_redis.bodog',
            'external'      => 'snc_redis.external',
            'total_balance' => 'snc_redis.total_balance',
            'ip_blocker'    => 'snc_redis.ip_blocker',
            'suncity'       => 'snc_redis.suncity'
        ];

        // 所有mysql資料庫
        $sqlClient = [
            'default',
            'his',
            'entry',
            'share',
            'outside',
            'ip_blocker'
        ];

        // 兩欄位皆不輸入時列出全部資訊
        if (empty($redis) && empty($mysql)) {
            $redis = array_keys($redisClient);
            $mysql = $sqlClient;
        }

        if (!empty($redis)) {
            foreach ($redis as $value) {
                // 檢查是否為有無redis的資料庫名稱陣列
                if (!key_exists($value, $redisClient)) {
                    throw new \InvalidArgumentException("Invalid Redis Name", 150960001);
                }

                // 檢測redis連線
                $output['redis'][$value] = $this->checkRedis($redisClient[$value]);

                if (key_exists('redis_error', $output['redis'][$value]) ) {
                    $output['result'] = 'error';
                }
            }
        }

        if (!empty($mysql)) {
            foreach ($mysql as $value) {
                // 檢查是否為有無mysql的資料庫名稱陣列
                if (!in_array($value, $sqlClient)) {
                    throw new \InvalidArgumentException("Invalid Database Name", 150960002);
                }

                // 檢測mysql連線
                $output['mysql'][$value] = $this->checkMysql($value);

                if (key_exists('mysql_error', $output['mysql'][$value])) {
                    $output['result'] = 'error';
                }
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 測試redis資料庫
     *
     * @param $redisName
     * @return array
     */
    private function checkRedis($redisName)
    {
        $output = [];

        $startTime = microtime(true);

        try {
            $redis = $this->get($redisName);
            // 測試redis
            $redis->get('1');
        } catch (\Exception $e) {
            $output['redis_error'][] = $e->getMessage();
        }

        $output['execution_time'] = round((microtime(true) - $startTime) * 1000);

        return $output;
    }

    /**
     * 測試資料庫
     *
     * @param $databaseName
     * @return array
     */
    private function checkMysql($databaseName)
    {
        $output = [];

        $startTime = microtime(true);

        try {
            $em = $this->getDoctrine()->getManager($databaseName);
            // 測試mysql
            $statement = $em->getConnection()->prepare('select 1');
            $statement->execute();
        } catch (\Exception $e) {
            $output['mysql_error'][] = $e->getMessage();
        }

        $output['execution_time'] = round((microtime(true) - $startTime) * 1000);

        return $output;
    }
}
