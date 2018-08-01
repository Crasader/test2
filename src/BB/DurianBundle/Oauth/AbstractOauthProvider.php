<?php
namespace BB\DurianBundle\Oauth;

abstract class AbstractOauthProvider
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appKey;

    /**
     * 重導向網址
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * oauth廠商的api網址
     *
     * @var string
     */
    protected $domain;

    /**
     * 發送request時, 內部主機的ip
     *
     * @var string
     */
    protected $ip;

    /**
     * @var Buzz\Message\Response
     */
    protected $response;

    /**
     * @var Buzz\Client\Curl
     */
    protected $client;

    /**
     * @param string $appId
     * @param string $appKey
     * @param string $redirectUrl
     * @param string $domain
     * @param string $ip
     */
    public function __construct(
        $appId,
        $appKey,
        $redirectUrl,
        $domain,
        $ip
    ) {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->redirectUrl = $redirectUrl;
        $this->domain = $domain;
        $this->ip = $ip;
    }

    /**
     * 設定Response物件
     * @param Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * 設定Client
     * @param Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * 利用oauth code取得存放在oauth廠商的使用者資料
     */
    abstract public function getUserProfileByCode($code);
}
