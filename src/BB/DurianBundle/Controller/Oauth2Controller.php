<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class Oauth2Controller extends Controller
{
    /**
     * 新增 Oauth2 client
     *
     * @Route("/oauth2/client",
     *        name = "api_oauth2_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $request = $request->request;
        $name = $request->get('name');
        $redirectUri = $request->get('redirect_uri');
        $domain = $request->get('domain');

        if (!$name) {
            throw new \InvalidArgumentException('No name specified', 150810026);
        }

        if (!$redirectUri) {
            throw new \InvalidArgumentException('No redirect_uri specified', 150810027);
        }

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150810030);
        }

        $emShare = $this->getEntityManager('share');
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if (!$domainConfig) {
            throw new \RuntimeException('Not a domain', 150810031);
        }

        $oauth2 = $this->get('durian.oauth2_server');
        $client = $oauth2->createClient($name, $redirectUri, $domain);

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $client
        ]);
    }

    /**
     * 編輯 Oauth2 client
     *
     * @Route("/oauth2/client/{clientId}",
     *        name = "api_oauth2_edit",
     *        requirements = {"clientId" = "\w+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param string $clientId
     * @return JsonResponse
     */
    public function editAction(Request $request, $clientId)
    {
        $request = $request->request;
        $name = $request->get('name');
        $redirectUri = $request->get('redirect_uri');

        $oauth2 = $this->get('durian.oauth2_server');
        $client = $oauth2->getClient($clientId);

        $data = [];
        if ($request->has('name') && $name != $client['name']) {
            $data['name'] = $name;
        }

        if ($request->has('redirect_uri') && $redirectUri != $client['redirect_uri']) {
            $data['redirect_uri'] = $redirectUri;
        }

        if ($data) {
            $client = $oauth2->editClient($clientId, $data);
        }

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $client
        ]);
    }

    /**
     * 刪除 Oauth2 client
     *
     * @Route("/oauth2/client/{clientId}",
     *        name = "api_oauth2_remove",
     *        requirements = {"clientId" = "\w+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param string $clientId
     * @return JsonResponse
     */
    public function removeAction(Request $request, $clientId)
    {
        $redirectUri = $request->request->get('redirect_uri');
        $domain = $request->request->get('domain');

        if (!$redirectUri) {
            throw new \InvalidArgumentException('No redirect_uri specified', 150810028);
        }

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150810033);
        }

        $oauth2 = $this->get('durian.oauth2_server');
        $client = $oauth2->getClient($clientId);

        if ($client['redirect_uri'] != $redirectUri) {
            throw new \RuntimeException('Redirect_uri not match', 150810029);
        }

        if ($client['domain'] != $domain) {
            throw new \RuntimeException('Domain not match', 150810032);
        }

        $oauth2->removeClient($clientId);

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 回傳 Oauth2 client
     *
     * @Route("/oauth2/client/{clientId}",
     *        name = "api_oauth2_get_client",
     *        requirements = {"clientId" = "\w+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $clientId
     * @return JsonResponse
     */
    public function getClientAction(Request $request, $clientId)
    {
        $redirectUri = $request->query->get('redirect_uri');

        if (!$redirectUri) {
            throw new \InvalidArgumentException('No redirect_uri specified', 150810001);
        }

        $oauth2 = $this->get('durian.oauth2_server');
        $client = $oauth2->getClient($clientId);

        if ($client['redirect_uri'] != $redirectUri) {
            throw new \RuntimeException('Redirect_uri not match', 150810002);
        }

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $client
        ]);
    }

    /**
     * 回傳 Oauth2 client 一覽表
     *
     * @Route("/oauth2/clients",
     *        name = "api_oauth2_get_clients",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getClientsAction()
    {
        $oauth2 = $this->get('durian.oauth2_server');
        $clients = $oauth2->getClients();

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $clients
        ]);
    }

    /**
     * 產生授權碼
     *
     * @Route("/oauth2/authenticate",
     *        name = "api_oauth2_authenticate",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticateAction(Request $request)
    {
        $query = $request->query;
        $responseType = $query->get('response_type', 'code');
        $state = $query->get('state');
        $sessionId = $request->headers->get('session-id');

        // 支援的模式
        $methods = ['code'];

        if (!in_array($responseType, $methods)) {
            throw new \InvalidArgumentException('Unsupported response_type', 150810004);
        }

        if (!$state) {
            throw new \InvalidArgumentException('No state specified', 150810005);
        }

        if (!$sessionId) {
            throw new \InvalidArgumentException('No session-id specified', 150810007);
        }

        $sessionBroker = $this->get('durian.session_broker');
        $exists = $sessionBroker->existsBySessionId($sessionId);

        if (!$exists) {
            throw new \RuntimeException('Session not found', 150810008);
        }

        $oauth2 = $this->get('durian.oauth2_server');

        if ($responseType == 'code') {
            $out = $oauth2->handleAuthorizationCode($request);
        }

        $out['state'] = $state;

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $out
        ]);
    }

    /**
     * 產生存取碼(access token)
     *
     * @Route("/oauth2/token",
     *        name = "api_oauth2_token",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateTokenAction(Request $request)
    {
        $authorization = $request->headers->get('Authorization');
        $grantType = $request->request->get('grant_type', 'authorization_code');

        // 支援方式
        $methods = ['authorization_code', 'refresh_token', 'password'];

        if (!in_array($grantType, $methods)) {
            throw new \InvalidArgumentException('Invalid grant_type', 150810009);
        }

        if (!$authorization) {
            throw new \InvalidArgumentException('Invalid Authorization', 150810010);
        }

        $prefix = substr($authorization, 0, 6);
        $authorization = substr($authorization, 6);
        $parts = explode(':', base64_decode($authorization));

        if ($prefix != 'Basic ') {
            throw new \InvalidArgumentException('Invalid Authorization format', 150810011);
        }

        if (!$parts || !isset($parts[0]) || !isset($parts[1])) {
            throw new \InvalidArgumentException('Invalid Authorization format', 150810012);
        }

        $clientId = $parts[0];
        $clientSecret = $parts[1];

        $oauth2 = $this->get('durian.oauth2_server');
        $oauth2->verifyAuthorization($clientId, $clientSecret);

        if ($grantType == 'authorization_code') {
            $out = $oauth2->grantByAuthorizationCode($request, $clientId);
        }

        if ($grantType == 'refresh_token') {
            $out = $oauth2->grantByRefreshCode($request, $clientId);
        }

        // RFC 定義要採用 username/password, 此處採用 session id 取代
        if ($grantType == 'password') {
            $out = $oauth2->grantBySession($request, $clientId);
        }

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $out
        ]);
    }

    /**
     * 利用 access token 取得使用者 session data
     *
     * @Route("/oauth2/user_by_token",
     *        name = "api_oauth2_get_user_by_token",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSessionDataActionByAccessToken(Request $request)
    {
        $token = $request->query->get('token');
        $clientId = $request->query->get('client_id');

        if (!$token) {
            throw new \InvalidArgumentException('No token specified', 150810037);
        }

        if (!$clientId) {
            throw new \InvalidArgumentException('No client_id specified', 150810034);
        }

        $oauth2 = $this->get('durian.oauth2_server');
        $sessionId = $oauth2->getSessionByToken($clientId, $token);

        $sessionBroker = $this->get('durian.session_broker');
        $sessionData = $sessionBroker->getBySessionId($sessionId);

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $sessionData
        ]);
    }

    /**
     * 回傳 Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}
