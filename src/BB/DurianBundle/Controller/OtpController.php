<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\GlobalIp;

class OtpController extends Controller
{
    /**
     * 取得 otp 驗證結果
     *
     * @Route("/otp/verify",
     *        name = "api_otp_verify",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $otpWorker = $this->container->get('durian.otp_worker');
        $validator = $this->container->get('durian.validator');
        $userValidator = $this->get('durian.user_validator');
        $redis = $this->get('snc_redis.default_client');

        $query = $request->query;
        $username = trim($query->get('username'));
        $domain = $query->get('domain');
        $otpToken = $query->get('otp_token', '');

        if (!$username) {
            throw new \InvalidArgumentException('No username specified', 150800001);
        }

        $userValidator->validateUsername($username);

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150800002);
        }

        if (!$validator->isInt($domain)) {
            throw new \InvalidArgumentException('Invalid domain', 150800005);
        }

        if (!$otpToken) {
            throw new \InvalidArgumentException('No otp_token specified', 150800003);
        }

        $criteria = [
            'domain' => $domain,
            'username' => $username
        ];

        $user = $em->getRepository('BBDurianBundle:User')->findOneBy($criteria);

        if (!$user) {
            throw new \RuntimeException('No such user', 150800004);
        }

        $config = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if (!$config || !$config->isVerifyOtp()) {
            throw new \RuntimeException('This user does not need to verify otp', 150800006);
        }

        $otpUser = $domain;

        if ($user->isSub()) {
            $otpUser .= '_sub';
        }

        $output['result'] = 'ok';

        if (!$redis->get('disable_otp')) {
            $result = $otpWorker->getOtpResult($otpUser, $otpToken, $user->getId(), $domain);

            if (!$result['response']) {
                $output['result'] = 'error';
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 驗證是否全域IP
     *
     * @Route("/global_ip/verify",
     *        name = "api_global_ip_verify",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyGlobalIpAction(Request $request)
    {
        $em = $this->getEntityManager();
        $validator = $this->container->get('durian.validator');

        $query = $request->query;
        $ip = $query->get('ip');

        $repo = $em->getRepository('BBDurianBundle:GlobalIp');
        $globalIp = $repo->findOneBy(['ip' => ip2long($ip)]);

        $output['result'] = 'ok';
        $output['ret'] = true;

        if (!$globalIp) {
            $output['ret'] = false;
        }

        return new JsonResponse($output);
    }

    /**
     * 新增全域IP
     *
     * @Route("/global_ip",
     *        name = "api_global_ip_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createGlobalIpAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        $request = $request->request;
        $ip = $request->get('ip');
        $memo = $request->get('memo', '');

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150800007);
        }

        if (!$validator->validateIp($ip)) {
            throw new \InvalidArgumentException('Invalid IP', 150800008);
        }

        $repo = $em->getRepository('BBDurianBundle:GlobalIp');
        $globalIp = $repo->findOneBy(['ip' => ip2long($ip)]);

        if ($globalIp) {
            throw new \RuntimeException('Global ip already exists', 150800009);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $gIp = new GlobalIp($ip);
            $gIp->setMemo($memo);
            $em->persist($gIp);
            $em->flush();

            $log = $operationLogger->create('global_ip', ['id' => $gIp->getId()]);
            $log->addMessage('ip', $ip);
            $log->addMessage('memo', $memo);
            $operationLogger->save($log);
            $emShare->flush();

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $gIp->toArray();

        return new JsonResponse($output);
    }

    /**
     * 刪除全域IP
     *
     * @Route("/global_ip",
     *        name = "api_global_ip_remove",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeGlobalIpAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $request = $request->request;
        $ip = $request->get('ip');

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150800010);
        }

        $repo = $em->getRepository('BBDurianBundle:GlobalIp');
        $globalIp = $repo->findOneBy(['ip' => ip2long($ip)]);

        if (!$globalIp) {
            throw new \RuntimeException('No such global ip', 150800011);
        }

        $log = $operationLogger->create('global_ip', ['id' => $globalIp->getId()]);
        $log->addMessage('ip', $ip);
        $operationLogger->save($log);

        $em->remove($globalIp);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳網段內全域IP
     *
     * @Route("/global_ip/check",
     *        name = "api_global_ip_check",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkGlobalIpAction(Request $request)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:GlobalIp');
        $query = $request->query;

        $ipStart = $query->get('ip_start');
        $ipEnd = $query->get('ip_end');

        $globalIps = $repo->getGlobalIp(ip2long($ipStart), ip2long($ipEnd));


        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($globalIps as $globalIp) {
            $output['ret'][] = $globalIp->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 設定 otp 總開關
     *
     * @Route("/otp/set",
     *        name = "api_otp_set",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setAction(Request $request)
    {
        $redis = $this->get('snc_redis.default_client');
        $request = $request->request;
        $disable = $request->get('disable', 0);

        $output['result'] = 'ok';

        if ($disable) {
            $redis->set('disable_otp', 1);
        } else {
            $redis->set('disable_otp', 0);
        }

        return new JsonResponse($output);
    }

    /**
     * 編輯全域IP備註
     *
     * @Route("/global_ip",
     *        name = "api_global_ip_edit",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editGlobalIpAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $request = $request->request;
        $ip = $request->get('ip');
        $memo = $request->get('memo', '');

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150800012);
        }

        $repo = $em->getRepository('BBDurianBundle:GlobalIp');
        $globalIp = $repo->findOneBy(['ip' => ip2long($ip)]);

        if (!$globalIp) {
            throw new \RuntimeException('No such global ip', 150800013);
        }

        $log = $operationLogger->create('global_ip', ['id' => $globalIp->getId()]);
        $log->addMessage('memo', $memo);
        $operationLogger->save($log);

        $globalIp->setMemo($memo);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $globalIp->toArray();

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
