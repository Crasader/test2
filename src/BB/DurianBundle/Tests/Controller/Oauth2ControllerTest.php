<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\Oauth2Controller;
use Symfony\Component\HttpFoundation\Request;

class Oauth2ControllerTest extends ControllerTest
{
    /**
     * 測試建立 Oauth2 client, 但未帶入 name
     */
    public function testCreateClientWithInvalidName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No name specified',
            150810026
        );

        $request = new Request([], ['redirect_uri' => 'non-empty']);
        $controller = new Oauth2Controller();
        $controller->createAction($request);
    }

    /**
     * 測試建立 Oauth2 client, 但未帶入 redirect_uri
     */
    public function testCreateClientWithInvalidRedirectUri()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No redirect_uri specified',
            150810027
        );

        $request = new Request([], ['name' => 'non-empty']);
        $controller = new Oauth2Controller();
        $controller->createAction($request);
    }

    /**
     * 測試建立 Oauth2 client, 但未帶入 domain
     */
    public function testCreateClientWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150810030
        );

        $request = new Request([], ['name' => 'non-empty', 'redirect_uri' => 'non-empty']);
        $controller = new Oauth2Controller();
        $controller->createAction($request);
    }

    /**
     * 測試刪除 oauth2 client, 未帶入 redirect_uri
     */
    public function testRemoveClientWithoutRedirectUri()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No redirect_uri specified',
            150810028
        );

        $request = new Request();
        $controller = new Oauth2Controller();
        $controller->removeAction($request, 'abc12345');
    }

    /**
     * 測試刪除 oauth2 client, 未帶入 Domain
     */
    public function testRemoveClientWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150810033
        );

        $request = new Request([], ['redirect_uri' => 'non-empty']);
        $controller = new Oauth2Controller();
        $controller->removeAction($request, 'abc12345');
    }

    /**
     * 測試取得 oauth2 client, 未帶入 redirect_uri
     */
    public function testGetClientWithoutRedirectUri()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No redirect_uri specified',
            150810001
        );

        $request = new Request();
        $controller = new Oauth2Controller();
        $controller->getClientAction($request, 'abc12345');
    }

    /**
     * 測試產生授權碼, 但 response_type 不支援
     */
    public function testAuthenticateWithWrongResponseType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Unsupported response_type',
            150810004
        );

        $query = [
            'response_type' => 'code11',
            'state' => 's12345',
            'client_id' => 'abc1234'
        ];
        $server = ['session-id' => 'ses123'];
        $request = new Request($query, [], [], [], [], $server);
        $controller = new Oauth2Controller();
        $controller->authenticateAction($request);
    }

    /**
     * 測試產生授權碼, 但未帶入 state
     */
    public function testAuthenticateWithoutState()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No state specified',
            150810005
        );

        $query = [
            'response_type' => 'code',
            'client_id' => 'abc1234'
        ];
        $server = ['session-id' => 'ses123'];
        $request = new Request($query, [], [], [], [], $server);
        $controller = new Oauth2Controller();
        $controller->authenticateAction($request);
    }

    /**
     * 測試產生授權碼, 但未帶入 session-id
     */
    public function testAuthenticateWithoutSessionId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No session-id specified',
            150810007
        );

        $query = [
            'response_type' => 'code',
            'state' => 's1234',
            'client_id' => 'abc12345'
        ];
        $request = new Request($query);
        $controller = new Oauth2Controller();
        $controller->authenticateAction($request);
    }

    /**
     * 測試產生存取碼, 但未帶入 Authorization
     */
    public function testGenerateTokenWithoutAuthorization()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Authorization',
            150810010
        );

        $params = ['grant_type' => 'authorization_code'];
        $request = new Request([], $params);
        $controller = new Oauth2Controller();
        $controller->generateTokenAction($request);
    }

    /**
     * 測試產生存取碼, 但帶入錯誤的 Authorization (basic)
     */
    public function testGenerateTokenWithWrongAuthorizationNoBasicString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Authorization format',
            150810011
        );

        $params = ['grant_type' => 'authorization_code'];
        $server = ['HTTP_Authorization' => 'asd'];
        $request = new Request([], $params, [], [], [], $server);
        $controller = new Oauth2Controller();
        $controller->generateTokenAction($request);
    }

    /**
     * 測試產生存取碼, 但帶入錯誤的 Authorization (client id & secert)
     */
    public function testGenerateTokenWithWrongAuthorizationNoClientIdAndSecret()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Authorization format',
            150810012
        );

        $params = ['grant_type' => 'authorization_code'];
        $server = ['HTTP_Authorization' => 'Basic asd'];
        $request = new Request([], $params, [], [], [], $server);
        $controller = new Oauth2Controller();
        $controller->generateTokenAction($request);
    }

    /**
     * 測試產生存取碼, 但 grant_type 不支援
     */
    public function testGenerateTokenWithWrongGrantType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid grant_type',
            150810009
        );

        $params = ['grant_type' => '123'];
        $server = ['HTTP_Authorization' => 'Basic YWJjMTIzNDU6c2VjcmV0LXRlc3Q='];
        $request = new Request([], $params, [], [], [], $server);
        $controller = new Oauth2Controller();
        $controller->generateTokenAction($request);
    }

    /**
     * 測試取得使用者 session 資料, 但未輸入 token
     */
    public function testGetSessionDataWithoutToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No token specified',
            150810037
        );

        $params = ['client_id' => 'abc'];
        $request = new Request($params);
        $controller = new Oauth2Controller();
        $controller->getSessionDataActionByAccessToken($request);
    }

    /**
     * 測試取得使用者 session 資料, 但未輸入 client_id
     */
    public function testGetSessionDataWithoutClientId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No client_id specified',
            150810034
        );

        $params = ['token' => 'abc'];
        $request = new Request($params);
        $controller = new Oauth2Controller();
        $controller->getSessionDataActionByAccessToken($request);
    }
}
