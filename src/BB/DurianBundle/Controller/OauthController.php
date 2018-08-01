<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Oauth;
use BB\DurianBundle\Entity\OauthUserBinding;
use Symfony\Component\HttpFoundation\Request;

class OauthController extends Controller
{

    /**
     * 取得使用者資訊
     *
     * @Route("/oauth/user_profile",
     *        name = "api_oauth_get_user_profile",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserProfileAction(Request $request)
    {
        $oauthGenerator = $this->get('durian.oauth_generator');
        $em = $this->getEntityManager();
        $query = $request->query;

        $oauthId = $query->get('oauth_id', '');
        $code = $query->get('code', '');

        if (!$oauthId) {
            throw new \InvalidArgumentException('Invalid oauth id', 150230001);
        }

        if (!$code) {
            throw new \InvalidArgumentException('No oauth code specified', 150230003);
        }

        $oauth = $em->find('BBDurianBundle:Oauth', $oauthId);
        if (!$oauth) {
            throw new \InvalidArgumentException('Invalid oauth id', 150230001);
        }

        $redirectIp = $this->container->getParameter("oauth_redirect_ip");
        $oauthProvider = $oauthGenerator->get($oauth, $redirectIp);

        $output['result'] = 'ok';
        $output['ret'] = $oauthProvider->getUserProfileByCode($code);

        return new JsonResponse($output);
    }

    /**
     * 取得Oauth設定
     *
     * @Route("/domain/{domain}/oauth",
     *        name = "api_oauth_get_by_domain",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getOauthByDomainAction($domain)
    {
        $em = $this->getEntityManager();

        $criteria = array('domain' => $domain);
        $oauths = $em->getRepository('BBDurianBundle:Oauth')->findBy($criteria);

        $output['result'] = 'ok';
        foreach ($oauths as $oauth) {
            $output['ret'][] = $oauth->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 利用id取得Oauth設定
     *
     * @Route("/oauth/{oauthId}",
     *        name = "api_oauth_get_by_id",
     *        requirements = {"oauthId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getOauthByIdAction($oauthId)
    {
        $em = $this->getEntityManager();

        $oauth = $em->find('BBDurianBundle:Oauth', $oauthId);
        if (empty($oauth)) {
            throw new \InvalidArgumentException('Oauth not exist', 150230012);
        }

        $output['result'] = 'ok';
        $output['ret'] = $oauth->toArray();

        return new JsonResponse($output);
    }

    /**
     * 新增Oauth設定
     *
     * @Route("/oauth",
     *        name = "api_oauth_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $vendorId = $request->get('vendor_id');
        $domain = $request->get('domain');
        $appId = trim($request->get('app_id', ''));
        $appKey = $request->get('app_key', '');
        $redirectUrl = trim($request->get('redirect_url', ''));

        if (empty($appId)) {
            throw new \InvalidArgumentException('Invalid app id', 150230004);
        }

        if (empty($appKey)) {
            throw new \InvalidArgumentException('Invalid app key', 150230005);
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$appId, $appKey, $redirectUrl];
        $validator->validateEncode($checkParameter);

        $vendor = $em->find('BBDurianBundle:OauthVendor', $vendorId);
        if (empty($vendor)) {
            throw new \InvalidArgumentException('Invalid oauth vendor', 150230008);
        }

        $domainUser = $em->find('BBDurianBundle:User', $domain);
        if (empty($domainUser)) {
            throw new \RuntimeException('Not a domain', 150230015);
        }

        if (($domainUser->getRole() != 7)) {
            throw new \RuntimeException('Not a domain', 150230015);
        }

        $oauth = new Oauth(
            $vendor,
            $domain,
            $appId,
            $appKey,
            $redirectUrl
        );
        $em->persist($oauth);
        $em->flush();

        $log = $operationLogger->create('oauth', ['id' => $oauth->getId()]);
        $log->addMessage('vendor', $vendorId);
        $log->addMessage('domain', $domain);
        $log->addMessage('app_id', $appId);
        $log->addMessage('app_key', $appKey);
        $log->addMessage('redirect_url', $redirectUrl);
        $operationLogger->save($log);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $oauth->toArray();

        return new JsonResponse($output);
    }

    /**
     * 編輯Oauth設定
     *
     * @Route("/oauth/{oauthId}",
     *        name = "api_oauth_edit",
     *        requirements = {"oauthId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $oauthId
     * @return JsonResponse
     */
    public function editAction(Request $request, $oauthId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $vendorId = $request->get('vendor_id');
        $appId = trim($request->get('app_id'));
        $appKey = $request->get('app_key');
        $redirectUrl = trim($request->get('redirect_url'));

        // 驗證參數編碼是否為utf8
        $checkParameter = [$appId, $appKey, $redirectUrl];
        $validator->validateEncode($checkParameter);

        $oauth = $em->find('BBDurianBundle:Oauth', $oauthId);
        if (!$oauth) {
            throw new \InvalidArgumentException('Invalid oauth id', 150230001);
        }

        $log = $operationLogger->create('oauth', ['id' => $oauthId]);
        if ($request->has('vendor_id')) {
            $vendor = $em->find('BBDurianBundle:OauthVendor', $vendorId);
            if (empty($vendor)) {
                throw new \InvalidArgumentException('Invalid oauth vendor', 150230008);
            }

            if ($oauth->getVendor()->getId() != $vendorId) {
                $log->addMessage('vendor', $oauth->getVendor()->getId(), $vendor->getId());
                $oauth->setVendor($vendor);
            }
        }

        if ($request->has('app_id')) {
            if ($oauth->getAppId() != $appId) {
                $log->addMessage('app_id', $oauth->getAppId(), $appId);
                $oauth->setAppId($appId);
            }
        }

        if ($request->has('app_key')) {
            if ($oauth->getAppKey() != $appKey) {
                $log->addMessage('app_key', $oauth->getAppKey(), $appKey);
                $oauth->setAppKey($appKey);
            }
        }

        if ($request->has('redirect_url')) {
            if ($oauth->getRedirectUrl() != $redirectUrl) {
                $log->addMessage('redirect_url', $oauth->getRedirectUrl(), $redirectUrl);
                $oauth->setRedirectUrl($redirectUrl);
            }
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $oauth->toArray();

        return new JsonResponse($output);
    }

    /**
     * 刪除Oauth設定
     *
     * @Route("/oauth/{oauthId}",
     *        name = "api_oauth_remove",
     *        requirements = {"oauthId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $oauthId
     * @return JsonResponse
     */
    public function removeAction($oauthId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $oauth = $em->find('BBDurianBundle:Oauth', $oauthId);
            if (!$oauth) {
                throw new \InvalidArgumentException('Invalid oauth id', 150230001);
            }

            $log = $operationLogger->create('oauth', ['id' => $oauthId]);
            $log->addMessage('removed', 'false', 'true');
            $operationLogger->save($log);

            $em->remove($oauth);
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 新增綁定使用者的oauth帳號資訊
     *
     * @Route("/oauth/binding",
     *        name = "api_oauth_create_binding",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBindingAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $bindingRepo = $em->getRepository('BBDurianBundle:OauthUserBinding');
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $userId = $request->get('user_id');
        $vendorId = $request->get('vendor_id', '');
        $openid = $request->get('openid');

        if (empty($openid)) {
            throw new \InvalidArgumentException('Invalid oauth openid', 150230006);
        }

        $validator->validateEncode($openid);

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 150230016);
        }

        $vendor = $em->find('BBDurianBundle:OauthVendor', $vendorId);
        if (empty($vendor)) {
            throw new \InvalidArgumentException('Invalid oauth vendor', 150230008);
        }
        $criteria = array(
            'userId' => $userId,
            'vendor' => $vendor,
        );
        $existBinding = $bindingRepo->findBy($criteria);

        if ($existBinding) {
            throw new \RuntimeException('Oauth binding already exist', 150230007);
        }

        $domain = $user->getDomain();
        $existBinding = $bindingRepo->getBindingBy($domain, $vendorId, $openid);

        if ($existBinding) {
            throw new \RuntimeException('Duplicate openid in the same domain', 150230013);
        }

        $binding = new OauthUserBinding(
            $userId,
            $vendor,
            $openid
        );

        $em->persist($binding);
        $em->flush();

        $id = $binding->getId();
        $log = $operationLogger->create('oauth_user_binding', ['id' => $id]);
        $log->addMessage('id', $id);
        $log->addMessage('user_id', $userId);
        $log->addMessage('oauth_vendor_id', $vendorId);
        $log->addMessage('openid', $openid);
        $operationLogger->save($log);

        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $binding->toArray();

        return new JsonResponse($output);
    }

    /**
     * 移除使用者綁定oauth設定
     *
     * @Route("/user/{userId}/oauth_binding",
     *        name = "api_oauth_remove_binding",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeBindingAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $vendorId = trim($request->get('vendor_id'));
        $operationLogger = $this->get('durian.operation_logger');
        $bindingRepo = $em->getRepository('BBDurianBundle:OauthUserBinding');

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 150230016);
        }

        if ($vendorId) {
            $vendor = $em->find('BBDurianBundle:OauthVendor', $vendorId);
            if (empty($vendor)) {
                throw new \InvalidArgumentException('Invalid oauth vendor', 150230008);
            }

            $majorKeys['oauth_vendor_id'] = $vendorId;
        }

        $oauthBindings = $bindingRepo->getBindingByUser($userId, $vendorId);
        $majorKeys['user_id'] =  $userId;

        if(!$oauthBindings) {
            throw new \RuntimeException('No user binding found', 150230014);
        }

        $log = $operationLogger->create('oauth_user_binding', $majorKeys);
        $log->addMessage('removed', 'false', 'true');
        $operationLogger->save($log);

        foreach ($oauthBindings as $oauthBinding) {
            $em->remove($oauthBinding);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 判斷oauth帳號是否已經跟使用者做綁定
     *
     * @Route("/oauth/is_binding",
     *        name = "api_oauth_is_binding",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function isBindingAction(Request $request)
    {
        $em = $this->getEntityManager();
        $query = $request->query;

        $vendorId = $query->get('vendor_id');
        $openid = $query->get('openid');
        $domain = $query->get('domain');

        if (empty($openid)) {
            throw new \InvalidArgumentException('Invalid oauth openid', 150230006);
        }

        if (empty($vendorId)) {
            throw new \InvalidArgumentException('Invalid oauth vendor', 150230008);
        }

        $vendor = $em->find('BBDurianBundle:OauthVendor', $vendorId);
        if (empty($vendor)) {
            throw new \InvalidArgumentException('Invalid oauth vendor', 150230008);
        }

        $criteria = ['vendorId' => $vendorId, 'openid' => $openid];
        if ($domain) {
            $criteria['domain'] = $domain;
        }

        $bindings = $em->getRepository('BBDurianBundle:OauthUserBinding')->getBindingArrayBy($criteria);

        $output['result'] = 'ok';
        $output['ret']['user_binding'] = $bindings;

        $output['ret']['is_binding'] = true;
        if (empty($bindings)) {
            $output['ret']['is_binding'] = false;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得所有OauthVendor
     *
     * @Route("/oauth/vendor",
     *        name = "api_oauth_get_all_vendor",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getAllOauthVendorAction()
    {
        $em = $this->getEntityManager();

        $vendors = $em->getRepository('BBDurianBundle:OauthVendor')->findAll();

        $output['ret'] = array();
        foreach ($vendors as $vendor) {
            $output['ret'][] = $vendor->toArray();
        }

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
