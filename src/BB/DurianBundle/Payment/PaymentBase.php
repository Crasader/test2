<?php

namespace BB\DurianBundle\Payment;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Buzz\Message\Request;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Aw\Nusoap\NusoapClient;

/**
 * 支付平台共用程式
 */
abstract class PaymentBase extends ContainerAware
{
    /**
     * 付款種類：現金
     */
    const PAYWAY_CASH = 1;

    /**
     * 付款種類：租卡
     */
    const PAYWAY_CARD = 2;

    /**
     * 支付平台選項
     *
     * @var array
     */
    protected $options = [];

    /**
     * 支付時要提交給支付平台的參數
     *
     * @var array
     */
    protected $requestData = [];

    /**
     * 解密驗證參數
     *
     * @var array
     */
    protected $verifyData = [];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [];

    /**
     * 查詢時要提交給支付平台的參數
     *
     * @var array
     */
    protected $trackingRequestData = [];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [];

    /**
     * 私鑰
     *
     * @var string
     */
    protected $privateKey;

    /**
     * @var \Buzz\Client\Curl | NusoapClient
     */
    protected $client;

    /**
     *
     * @var \Buzz\Message\Response
     */
    protected $response;

    /**
     * 額外的支付欄位
     *
     * @var array
     */
    protected $extraParams = [];

    /**
     * 明細id
     *
     * @var integer
     */
    protected $entryId;

    /**
     * 實名認證所需的參數欄位
     *
     * @var array
     */
    protected $realNameAuthParams = [];

    /**
     * 實名認證時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $realNameAuthRequestData = [];

    /**
     * 實名認證時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $realNameAuthRequireMap = [];

    /**
     * 實名認證時需要加密的參數
     *
     * @var array
     */
    protected $realNameAuthEncodeParams = [];

    /**
     * 出款時要提交給支付平台的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [];

    /**
     * 出款返回驗證需要加密的參數
     *
     * @var array
     */
    protected $withdrawDecodeParams = [];

    /**
     * 出款查詢時要提交給支付平台的參數
     *
     * @var array
     */
    protected $withdrawTrackingRequestData = [];

    /**
     * 出款查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawTrackingRequireMap = [];

    /**
     * 出款查詢時需要加密的參數
     *
     * @var array
     */
    protected $withdrawTrackingEncodeParams = [];

    /**
     * 出款查詢結果需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $withdrawTrackingDecodeParams = [];

    /**
     * 設定支付平台選項
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * 應答機制訊息，預設為success
     *
     * @var string
     */
    protected $msg = 'success';

    /**
     * Payment 專案表單提交的 method
     *
     * @var string
     */
    protected $payMethod = 'POST';

    /**
     * qrcode, 預設為空字串
     *
     * @var string
     */
    protected $qrcode = '';

    /**
     * html, 預設為空字串
     *
     * @var string
     */
    protected $html = '';

    /**
     * @param \Buzz\Client\Curl | NusoapClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * 設定支付平台金鑰
     *
     * @param string $privateKey
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * 取得額外的支付欄位
     *
     * @return array
     */
    public function getExtraParams()
    {
        return $this->extraParams;
    }

    /**
     * 取得實名認證所需的參數欄位
     *
     * @return array
     */
    public function getRealNameAuthParams()
    {
        return $this->realNameAuthParams;
    }

    /**
     * 設定明細id
     *
     * @param integer $entryId
     */
    public function setEntryId($entryId)
    {
        $this->entryId = $entryId;
    }

    /**
     * 取得明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 取得應答機制訊息
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * 取得 Payment 專案表單提交的 method
     *
     * @return string
     */
    public function getPayMethod()
    {
        return $this->payMethod;
    }

    /**
     * 設定解密參數
     *
     * @var array $verifyData 第三方返回驗證參數
     */
    public function setVerifyData($verifyData)
    {
        $this->verifyData = $verifyData;
    }

    /**
     * 處理訂單查詢支付平台返回的編碼
     *
     * @param array $response 訂單查詢的返回
     * @return array
     */
    public function processTrackingResponseEncoding($response)
    {
        // kue 先將回傳資料先做 base64 編碼，因此需先解開
        $body = trim(base64_decode($response['body']));

        // 先判斷返回是否為 XML 格式(因轉碼後會無法成功判斷)
        $isXml = $this->isXml($body);

        // 取得 Header 內的編碼來當做轉換編碼時的依據
        $contentType = null;

        if (isset($response['header']['content-type'])) {
            $contentType = $response['header']['content-type'];
        }

        // 如果 Header 沒有給定 charset, 則使用 UTF-8 做轉換編碼依據
        if (!empty($contentType)) {
            $charset = [];

            //要抓的的格式為英數字及-號
            preg_match('/charset=([\w-]+)/', $contentType, $charset);

            if (!isset($charset[1])) {
                $detach = ['GB2312', 'UTF-8', 'GBK'];
                $charset[1] = mb_detect_encoding($body, $detach);
            }

            $body = iconv($charset[1], 'UTF-8', $body);
        }

        // 如果是 XML，因編碼已經轉換成 UTF-8，要把 encoding 改成 UTF-8
        if ($isXml) {
            $body = preg_replace('/ encoding="[\w-]+"/', ' encoding="UTF-8"', $body);
        }
        $response['body'] = $body;

        return $response;
    }

    /**
     * 取得商家附加設定值
     *
     * @param array $names 商家設定名稱
     * @return array
     */
    protected function getMerchantExtraValue($names)
    {
        $values = [];
        $merchantExtra = $this->options['merchant_extra'];

        foreach ($names as $name) {
            if (!array_key_exists($name, $merchantExtra)) {
                throw new PaymentException('No merchant extra value specified', 180143);
            }

            $values[$name] = $merchantExtra[$name];
        }

        return $values;
    }

    /**
     * 驗證key
     */
    protected function verifyPrivateKey()
    {
        if (trim($this->privateKey) === '') {
            throw new PaymentException('No privateKey specified', 180142);
        }
    }

    /*
     * 將XML格式轉成array
     *
     * @param string $content xml格式字串
     * @return array
     */
    protected function xmlToArray($content)
    {
        $decodeContent = urldecode($content);

        //如果不是xml格式，就丟例外(因為如果返回的是錯誤訊息就不會是xml格式)
        if (!$this->isXml($decodeContent)) {
            throw new PaymentConnectionException('Invalid XML format', 180121, $this->getEntryId());
        }

        $encoders = [new XmlEncoder()];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $parseData = $serializer->decode($decodeContent, 'xml');

        return $parseData;
    }

    /**
     * 將array格式轉成xml
     *
     * @param array $data
     * @param array $context
     * @param string $rootNodeName
     * @return string
     */
    protected function arrayToXml($data, $context, $rootNodeName = 'response')
    {
        $encoders = [new XmlEncoder($rootNodeName)];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($data, 'xml', $context);

        return str_replace("\n", '', $xml);
    }

    /**
     * 使用nusoap對外連線
     *
     * @param array $nusoapParam nusoap相關參數
     * @return mixed 返回結果
     */
    protected function soapRequest($nusoapParam)
    {
        foreach ($nusoapParam['serverIp'] as $ip) {
            try {
                $soapUrl = "http://{$nusoapParam['host']}{$nusoapParam['uri']}";

                $client = new NusoapClient($soapUrl, $nusoapParam['wsdl'], $ip, true);

                if ($nusoapParam['wsdl']) {
                    $client->setEndpoint($soapUrl);
                }

                if ($this->client) {
                    $client = $this->client;
                }

                try {
                    $result = $client->call($nusoapParam['function'], $nusoapParam['callParams']);
                } catch (\Exception $e) {
                    throw new PaymentConnectionException('Payment Gateway connection failure', 180088, $this->getEntryId());
                }

                // 紀錄log
                $logger = $this->container->get('durian.payment_logger');

                $message = [
                    'serverIp' => $ip,
                    'host'     => $nusoapParam['host'],
                    'method'   => 'nusoap',
                    'uri'      => $nusoapParam['uri'],
                    'param'    => htmlspecialchars_decode($client->request),
                    'output'   => $result
                ];

                if (is_array($result)) {
                    $message['output'] = urldecode(http_build_query($result));
                }

                $logger->record($message);

                if (!$result) {
                    throw new PaymentConnectionException('Empty Payment Gateway response', 180089, $this->getEntryId());
                }

                return $result;
            } catch (\Exception $e) {
                if (end($nusoapParam['serverIp']) == $ip) {
                    throw $e;
                }

                continue;
            }
        }
    }

    /**
     * 回傳RSA私鑰
     *
     * @return resource
     */
    public function getRsaPrivateKey()
    {
        $passphrase = '';
        $operator = new Operator();

        // 因存入DB會先做base64_encode，因此取出來要先base64_decode
        $content = base64_decode($this->options['rsa_private_key']);

        //如果取到的fileContent欄位是空的就要丟例外
        if (!$content) {
            throw new PaymentException('Rsa private key is empty', 180092);
        }

        $content = $operator->refreshPrivateKey($content);
        $privateKey = openssl_pkey_get_private($content, $passphrase);

        if (!$privateKey) {
            throw new PaymentException('Get rsa private key failure', 180093);
        }

        return $privateKey;
    }

    /**
     * 回傳RSA公鑰
     *
     * @return resource
     */
    public function getRsaPublicKey()
    {
        // 因存入DB會先做base64_encode，因此取出來要先base64_decode
        $content = base64_decode($this->options['rsa_public_key']);
        $operator = new Operator();

        //如果取到的fileContent欄位是空的就要丟例外
        if (!$content) {
            throw new PaymentException('Rsa public key is empty', 180095);
        }

        $content = $operator->refreshPublicKey($content);
        $publicKey = openssl_pkey_get_public($content);

        if (!$publicKey) {
            throw new PaymentException('Get rsa public key failure', 180096);
        }

        return $publicKey;
    }

    /**
     * 設定qrcode
     *
     * @param string $qrcode
     */
    protected function setQrcode($qrcode)
    {
        $this->qrcode = $qrcode;
    }

    /**
     * 回傳qrcode
     *
     * @return string
     */
    public function getQrcode()
    {
        return $this->qrcode;
    }

    /**
     * 設定html
     *
     * @param string $html
     */
    protected function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * 回傳html
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * 設定付款種類
     *
     * @param integer $payway
     */
    public function setPayway($payway)
    {
        $this->payway = $payway;
    }

    /**
     * 回傳付款種類
     *
     * @return integer
     */
    public function getPayway()
    {
        return $this->payway;
    }

    /**
     * 檢查支援的出款銀行
     *
     * @var integer $bankInfoId 銀行id
     */
    public function checkWithdrawBankSupport($bankInfoId)
    {
        if (!array_key_exists($bankInfoId, $this->withdrawBankMap)) {
            throw new PaymentException('BankInfo is not supported by PaymentGateway', 150180195);
        }
    }

    /**
     * 訂單查詢
     *
     */
    public function paymentTracking()
    {
        $this->setTrackingData();
        $this->tracking();
    }

    /**
     * 發送curl請求
     *
     * @param array $curlParam 參數說明如下
     *     method  string 提交方式
     *     uri     string
     *     ip      array
     *     host    string
     *     param   array  提交的參數
     *     header  array  需要的header
     *     timeout integer 連線超時時間
     *     charset string 字符集
     *
     * @return string Response Content
     */
    protected function curlRequest($curlParam)
    {
        $logger = $this->container->get('durian.payment_logger');

        if ($curlParam['method'] === 'GET') {
            $curlParam['uri'] .= '?' . $curlParam['param'];
        }

        foreach ($curlParam['ip'] as $ip) {
            try {
                $request = new Request($curlParam['method']);

                if ($curlParam['method'] === 'POST') {
                    $request->setContent($curlParam['param']);
                }

                $request->fromUrl($ip . $curlParam['uri']);
                $request->setHeaders($curlParam['header']);
                $request->addHeader("Host: {$curlParam['host']}");

                $client = new Curl();

                if ($this->client) {
                    $client = $this->client;
                }

                //關閉curl ssl憑證檢查
                $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
                $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

                // 如果沒有指定超時時間，預設為10秒
                if (!isset($curlParam['timeout'])) {
                    $curlParam['timeout'] = 10;
                }

                $client->setOption(CURLOPT_TIMEOUT, $curlParam['timeout']);

                $response = new Response();

                try {
                    $client->send($request, $response);
                } catch (\Exception $e) {
                    throw new PaymentConnectionException('Payment Gateway connection failure', 180088, $this->getEntryId());
                }

                if ($this->response) {
                    $response = $this->response;
                }

                $result = trim($response->getContent());

                // 先判斷返回是否為XML格式(因轉碼後會無法成功判斷)
                $isXml = $this->isXml($result);

                //取得Header內的編碼來當做轉換編碼時的依據
                $header = $response->getHeader('Content-Type');

                // 若指定字符集，則使用該字符集轉換編碼
                if (isset($curlParam['charset'])) {
                    $header = 'charset=' . $curlParam['charset'];
                }

                //如果 Header 沒有給定 charset, 則使用 UTF-8 做轉換編碼依據
                if (!empty($header)) {
                    $charset = [];

                    //要抓的的格式為英數字及-號
                    preg_match('/charset=([\w-]+)/', $header, $charset);

                    if (!isset($charset[1])) {
                        $detach = ['GB2312', 'UTF-8', 'GBK'];
                        $charset[1] = mb_detect_encoding($result, $detach);
                    }

                    $result = iconv($charset[1], 'UTF-8', $result);
                }

                //紀錄log
                $message = [
                    'serverIp' => $ip,
                    'host' => $curlParam['host'],
                    'method' => $curlParam['method'],
                    'uri' => $curlParam['uri'],
                    'param' => $this->curlRequestDecode($curlParam['param']),
                    'output' => urldecode($this->curlResponseDecode($result))
                ];

                $logger->record($message);

                if ($response->getStatusCode() != 200) {
                    throw new PaymentConnectionException('Payment Gateway connection failure', 180088, $this->getEntryId());
                }

                if (!$result) {
                    throw new PaymentConnectionException('Empty Payment Gateway response', 180089, $this->getEntryId());
                }

                // 如果是XML，因編碼已經轉換成UTF-8，要把encoding改成UTF-8
                if ($isXml) {
                    $result = preg_replace('/ encoding="[\w-]+"/', ' encoding="UTF-8"', $result);
                }

                return $result;
            } catch (\Exception $e) {
                if (end($curlParam['ip']) == $ip) {
                    throw $e;
                }

                continue;
            }
        }
    }

    /**
     * 發送curl請求，不驗證status code
     * 因有些非200會回傳錯誤訊息，可用來判斷出錯原因
     *
     * @param array $curlParam 參數說明如下
     *     method  string 提交方式
     *     uri     string
     *     ip      array
     *     host    string
     *     param   array 提交的參數
     *     header  array 需要的header
     *     timeout integer 連線超時時間
     *     charset string 字符集
     *
     * @return string Response Content
     */
    protected function curlRequestWithoutValidStatusCode($curlParam)
    {
        $logger = $this->container->get('durian.payment_logger');

        if ($curlParam['method'] === 'GET') {
            $curlParam['uri'] .= '?' . $curlParam['param'];
        }

        foreach ($curlParam['ip'] as $ip) {
            try {
                $request = new Request($curlParam['method']);

                if ($curlParam['method'] === 'POST') {
                    $request->setContent($curlParam['param']);
                }

                $request->fromUrl($ip . $curlParam['uri']);
                $request->setHeaders($curlParam['header']);
                $request->addHeader("Host: {$curlParam['host']}");

                $client = new Curl();

                if ($this->client) {
                    $client = $this->client;
                }

                // 關閉curl ssl憑證檢查
                $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
                $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

                // 如果沒有指定超時時間，預設為10秒
                if (!isset($curlParam['timeout'])) {
                    $curlParam['timeout'] = 10;
                }

                $client->setOption(CURLOPT_TIMEOUT, $curlParam['timeout']);

                $response = new Response();

                try {
                    $client->send($request, $response);
                } catch (\Exception $e) {
                    throw new PaymentConnectionException('Payment Gateway connection failure', 180088, $this->getEntryId());
                }

                if ($this->response) {
                    $response = $this->response;
                }

                $result = trim($response->getContent());

                // 先判斷返回是否為XML格式(因轉碼後會無法成功判斷)
                $isXml = $this->isXml($result);

                // 取得Header內的編碼來當做轉換編碼時的依據
                $header = $response->getHeader('Content-Type');

                // 若指定字符集，則使用該字符集轉換編碼
                if (isset($curlParam['charset'])) {
                    $header = 'charset=' . $curlParam['charset'];
                }

                // 如果 Header 沒有給定 charset, 則使用 UTF-8 做轉換編碼依據
                if (!empty($header)) {
                    $charset = [];

                    // 要抓的的格式為英數字及-號
                    preg_match('/charset=([\w-]+)/', $header, $charset);

                    if (!isset($charset[1])) {
                        $detach = ['GB2312', 'UTF-8', 'GBK'];
                        $charset[1] = mb_detect_encoding($result, $detach);
                    }

                    $result = iconv($charset[1], 'UTF-8', $result);
                }

                // 紀錄log
                $message = [
                    'serverIp' => $ip,
                    'host' => $curlParam['host'],
                    'method' => $curlParam['method'],
                    'uri' => $curlParam['uri'],
                    'param' => $this->curlRequestDecode($curlParam['param']),
                    'output' => urldecode($this->curlResponseDecode($result))
                ];

                $logger->record($message);

                if (!$result) {
                    throw new PaymentConnectionException('Empty Payment Gateway response', 180089, $this->getEntryId());
                }

                // 如果是XML，因編碼已經轉換成UTF-8，要把encoding改成UTF-8
                if ($isXml) {
                    $result = preg_replace('/ encoding="[\w-]+"/', ' encoding="UTF-8"', $result);
                }

                return $result;
            } catch (\Exception $e) {
                if (end($curlParam['ip']) == $ip) {
                    throw $e;
                }

                continue;
            }
        }
    }

    /**
     * 支付驗證
     */
    protected function payVerify()
    {
        foreach (array_values($this->requireMap) as $internalKey) {
            if (!isset($this->options[$internalKey]) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No pay parameter specified', 180145);
            }
        }
    }

    /**
     * 支付結果驗證
     */
    protected function payResultVerify()
    {
        foreach ($this->decodeParams as $paymentKey => $require) {
            if ($require && !isset($this->options[$paymentKey])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }
    }

    /**
     * 訂單查詢驗證
     */
    protected function trackingVerify()
    {
        foreach (array_values($this->trackingRequireMap) as $internalKey) {
            if (!isset($this->options[$internalKey]) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No tracking parameter specified', 180138);
            }
        }
    }

    /**
     * 訂單查詢結果驗證
     *
     * @param array $parseData
     */
    protected function trackingResultVerify($parseData)
    {
        foreach ($this->trackingDecodeParams as $paymentKey => $require) {
            if ($require && !isset($parseData[$paymentKey])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }
        }
    }

    /**
     * 解析網址
     *
     * @param string $payUrl 解析的網址
     * @return array
     */
    protected function parseUrl($payUrl)
    {
        $parseUrl = parse_url($payUrl);

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $params = [];

        if (isset($parseUrl['query'])) {
            parse_str($parseUrl['query'], $params);
        }

        $port = isset($parseUrl['port']) ? ':' . $parseUrl['port'] : '';

        $url = sprintf(
            '%s://%s%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $port,
            $parseUrl['path']
        );

        return [
            'url' => $url,
            'params' => $params,
        ];
    }

    /**
     * 支付實名認證參數驗證
     */
    protected function payRealNameAuthVerify()
    {
        $realNameAuthParams = $this->options['real_name_auth_params'];

        foreach (array_values($this->requestRealNameAuthMap) as $internalKey) {
            if (!isset($realNameAuthParams[$internalKey]) || trim($realNameAuthParams[$internalKey]) === '') {
                throw new PaymentException('No pay real name authentication parameter specified', 150180187);
            }
        }
    }

    /**
     * 驗證實名認證所需參數
     */
    protected function authenticationVerify()
    {
        foreach (array_values($this->realNameAuthRequireMap) as $internalKey) {
            if (!isset($this->options[$internalKey]) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No authentication parameter specified', 150180183);
            }
        }
    }

    /**
     * 出款驗證
     */
    protected function withdrawVerify()
    {
        foreach (array_values($this->withdrawRequireMap) as $internalKey) {
            if (!array_key_exists($internalKey, $this->options) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No withdraw parameter specified', 150180196);
            }
        }
    }

    /**
     * 出款結果驗證
     *
     * @param array $parseData
     */
    protected function withdrawResultVerify($parseData)
    {
        foreach ($this->withdrawDecodeParams as $paymentKey => $require) {
            if ($require && !array_key_exists($paymentKey, $parseData)) {
                throw new PaymentException('No withdraw return parameter specified', 150180209);
            }
        }
    }

    /**
     * 出款訂單查詢驗證
     */
    protected function withdrawTrackingVerify()
    {
        foreach (array_values($this->withdrawTrackingRequireMap) as $internalKey) {
            if (!isset($this->options[$internalKey]) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No withdraw tracking parameter specified', 150180199);
            }
        }
    }

    /**
     * 出款訂單查詢結果驗證
     *
     * @param array $parseData
     */
    protected function withdrawTrackingResultVerify($parseData)
    {
        foreach ($this->withdrawTrackingDecodeParams as $paymentKey => $require) {
            if ($require && !array_key_exists($paymentKey, $parseData)) {
                throw new PaymentException('No withdraw tracking return parameter specified', 150180200);
            }
        }
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeStr = '';

        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 檢查是否為XML格式
     *
     * @param string $content
     * @return integer
     */
    protected function isXml($content)
    {
        $isXml = true;
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $isXml = false;
        }

        return $isXml;
    }

    /**
     * 設定入款明細的支付平台參考編號
     *
     * @param string $refId
     */
    protected function setCashDepositEntryRefId($refId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $entry = $repo->findOneBy(['id' => $this->getEntryId()]);

        $entry->setRefId($refId);

        $em->flush();
    }

    /**
     * 設定出款明細的支付平台參考編號
     *
     * @param string $refId
     */
    protected function setCashWithdrawEntryRefId($refId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $entry = $repo->findOneBy(['id' => $this->getEntryId()]);

        $entry->setRefId($refId);

        $em->flush();
    }

    /**
     * 設定租卡入款明細的支付平台參考編號
     *
     * @param string $refId
     */
    protected function setCardDepositEntryRefId($refId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $entry = $repo->findOneBy(['id' => $this->getEntryId()]);

        $entry->setRefId($refId);

        $em->flush();
    }

    /**
     * PHP DES 加密程式
     *
     * @param string $key 密鑰（八個字元內）
     * @param string $encrypt 要加密的明文
     * @return string 密文
     */
    protected function encrypt($key, $encrypt)
    {
        if (strlen($key) != 8) {
            throw new PaymentException('DES encrypt failed', 150180177);
        }

        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $pad = $size - (strlen($encrypt) % $size);
        $encrypt = $encrypt . str_repeat(chr($pad), $pad);
        $data = mcrypt_encrypt(MCRYPT_DES, $key, $encrypt, MCRYPT_MODE_CBC, $key);

        return base64_encode($data);
    }

    /**
     * PHP DES 解密程式
     *
     * @param string $key 密鑰（八個字元內）
     * @param string $decrypt 要解密的密文
     * @return string 明文
     */
    protected function decrypt($key, $decrypt)
    {
        $decrypt = base64_decode($decrypt);
        $decrypt = mcrypt_decrypt(MCRYPT_DES, $key, $decrypt, MCRYPT_MODE_CBC, $key);
        $pad = ord($decrypt{strlen($decrypt) - 1});

        if ($pad > strlen($decrypt)) {
            throw new PaymentException('DES decrypt failed', 150180175);
        }

        if (strspn($decrypt, chr($pad), strlen($decrypt) - $pad) != $pad) {
            throw new PaymentException('DES decrypt failed', 150180175);
        }

        return substr($decrypt, 0, -1 * $pad);
    }

    /**
     * 設定提交參數
     */
    protected function setRequestData()
    {
        $orderId = $this->options['orderId'];

        // 由訂單號生成公私鑰欄位加密密鑰
        $key = substr(md5($orderId), -8);

        $this->requestData = [
            'gateway_class_name' => $this->options['gateway_class_name'],
            'merchant_number' => $this->options['number'],
            'order_number' => $this->options['orderId'],
            'amount' => $this->options['amount'],
            'order_create_date' => $this->options['orderCreateDate'],
            'notify_url' => $this->options['notify_url'],
            'client_ip' => $this->options['ip'],
            'method_id' => $this->options['paymentVendorId'],
            'bank_id' => '',
            'extra' => $this->options['merchant_extra'],
            'rsa_private_key' => openssl_encrypt($this->options['rsa_private_key'], 'des-cbc', $key, 0, $key),
            'rsa_public_key' => openssl_encrypt($this->options['rsa_public_key'], 'des-cbc', $key, 0, $key),
            'user_agent' => $this->options['user_agent'],
            'private_key' => openssl_encrypt($this->privateKey, 'des-cbc', $key, 0, $key),
            'url' => $this->options['postUrl'],
        ];
    }

    /**
     * 設定提交參數
     */
    protected function setTrackingData()
    {
        $orderId = $this->options['orderId'];

        // 由訂單號生成公私鑰欄位加密密鑰
        $key = substr(md5($orderId), -8);

        $this->trackingRequestData = [
            'gateway_class_name' => $this->options['gateway_class_name'],
            'merchant_number' => $this->options['number'],
            'order_number' => $this->options['orderId'],
            'amount' => $this->options['amount'],
            'order_create_date' => $this->options['orderCreateDate'],
            'method_id' => $this->options['paymentVendorId'],
            'bank_id' => '',
            'extra' => $this->options['merchant_extra'],
            'rsa_private_key' => openssl_encrypt($this->options['rsa_private_key'], 'des-cbc', $key, 0, $key),
            'rsa_public_key' => openssl_encrypt($this->options['rsa_public_key'], 'des-cbc', $key, 0, $key),
            'private_key' => openssl_encrypt($this->privateKey, 'des-cbc', $key, 0, $key),
            'url' => $this->options['reopUrl'],
        ];
    }

    /**
     * 取得金流平台上金流的支付參數
     *
     * @return array 支付提交參數
     */
    protected function getPaymentDepositParams()
    {
        $pinkIp = parse_url($this->container->getParameter('pink_ip'));
        $pinkHost = $this->container->getParameter('pink_host');
        $token = $this->container->getParameter('trade_token');
        $orderId = $this->options['orderId'];

        $header = ['token' => $token];

        if (isset($pinkIp['port'])) {
            $header['Port'] = $pinkIp['port'];
        }

        // 取得驗證碼
        $captchaParams = [
            'method' => 'POST',
            'uri' => "/api/trade/v1/captcha/{$orderId}/create",
            'ip' => [$pinkIp['host']],
            'host' => $pinkHost,
            'param' => http_build_query(['length' => 8]),
            'header' => $header,
        ];

        $captchaResponse = $this->curlRequest($captchaParams);
        $captchaData = json_decode($captchaResponse, true);

        if (!isset($captchaData['result'])) {
            throw new PaymentException('Get captcha error', 150180212);
        }

        if ($captchaData['result'] !== 'ok') {
            if (isset($captchaData['code']) && !isset($captchaData['msg'])) {
                throw new PaymentException($captchaData['msg'], $captchaData['code']);
            }

            throw new PaymentException('Get captcha fail', 150180213);
        }

        if (!isset($captchaData['ret']['captcha'])) {
            throw new PaymentException('No return captcha', 150180214);
        }
        $captcha = $captchaData['ret']['captcha'];

        // 解密驗證
        $depositParams = [
            'method' => 'POST',
            'uri' => "/api/trade/v1/deposit/{$orderId}/parameters",
            'ip' => [$pinkIp['host']],
            'host' => $pinkHost,
            'param' => http_build_query(
                ['data' => openssl_encrypt(json_encode($this->requestData), 'des-cbc', $captcha, 0, $captcha)]
            ),
            'header' => $header,
            'timeout' => 30,
        ];

        // 取得支付參數
        $depositResponse = $this->curlRequest($depositParams);
        $depositData = json_decode($depositResponse, true);

        if (!isset($depositData['result'])) {
            throw new PaymentException('Get deposit params error', 1501802215);
        }

        if ($depositData['result'] !== 'ok') {
            if (isset($depositData['code']) && isset($depositData['msg'])) {
                throw new PaymentException($depositData['msg'], $depositData['code']);
            }

            throw new PaymentException('Get deopsit params fail', 150180216);
        }

        if (!isset($depositData['ret'])) {
            throw new PaymentException('No return deposit data', 150180217);
        }

        $ret = $depositData['ret']['ret'];

        $this->payMethod = $ret['method'];
        $this->setQrcode($ret['qrcode']);
        $this->setHtml($ret['html']);

        $output = [
            'method' => $ret['method'],
            'post_url' => $ret['url'],
            'params' => $ret['data_params'],
        ];

        return $output;
    }

    /**
     * 金流平台上金流解密驗證結果
     *
     * @return string 應答機制
     */
    protected function paymentVerify()
    {
        $pinkIp = parse_url($this->container->getParameter('pink_ip'));
        $pinkHost = $this->container->getParameter('pink_host');
        $token = $this->container->getParameter('trade_token');
        $orderId = $this->verifyData['order_number'];

        $header = ['token' => $token];

        if (isset($pinkIp['port'])) {
            $header['Port'] = $pinkIp['port'];
        }

        // 取得驗證碼
        $captchaParams = [
            'method' => 'POST',
            'uri' => "/api/trade/v1/captcha/{$orderId}/create",
            'ip' => [$pinkIp['host']],
            'host' => $pinkHost,
            'param' => http_build_query(['length' => 8]),
            'header' => $header,
        ];

        $captchaResponse = $this->curlRequest($captchaParams);
        $captchaData = json_decode($captchaResponse, true);

        if (!isset($captchaData['result'])) {
            throw new PaymentException('Get captcha error', 150180212);
        }

        if ($captchaData['result'] !== 'ok') {
            if (isset($captchaData['code']) && !isset($captchaData['msg'])) {
                throw new PaymentException($captchaData['msg'], $captchaData['code']);
            }

            throw new PaymentException('Get captcha fail', 150180213);
        }

        if (!isset($captchaData['ret']['captcha'])) {
            throw new PaymentException('No return captcha', 150180214);
        }
        $captcha = $captchaData['ret']['captcha'];

        // 解密驗證
        $depositParams = [
            'method' => 'POST',
            'uri' => "/api/trade/v1/deposit/{$orderId}/verify",
            'ip' => [$pinkIp['host']],
            'host' => $pinkHost,
            'param' => http_build_query(
                ['data' => openssl_encrypt(json_encode($this->verifyData), 'des-cbc', $captcha, 0, $captcha)]
            ),
            'header' => $header,
        ];

        // 取得支付參數
        $depositResponse = $this->curlRequest($depositParams);
        $depositData = json_decode($depositResponse, true);

        if (!isset($depositData['result'])) {
            throw new PaymentException('deposit verify error', 150180218);
        }

        if ($depositData['result'] !== 'ok') {
            if (isset($depositData['code']) && isset($depositData['msg'])) {
                throw new PaymentException($depositData['msg'], $depositData['code']);
            }

            throw new PaymentException('deposit verify fail', 150180219);
        }

        if (!isset($depositData['ret'])) {
            throw new PaymentException('No return deposit verify data', 150180220);
        }

        $this->msg = $depositData['ret']['msg'];
    }

    /**
     * 取得金流平台上金流的支付參數
     *
     * @return array 支付提交參數
     */
    public function tracking()
    {
        $pinkIp = parse_url($this->container->getParameter('pink_ip'));
        $pinkHost = $this->container->getParameter('pink_host');
        $token = $this->container->getParameter('trade_token');
        $orderId = $this->options['orderId'];

        $header = ['token' => $token];

        if (isset($pinkIp['port'])) {
            $header['Port'] = $pinkIp['port'];
        }

        // 取得驗證碼
        $captchaParams = [
            'method' => 'POST',
            'uri' => "/api/trade/v1/captcha/{$orderId}/create",
            'ip' => [$pinkIp['host']],
            'host' => $pinkHost,
            'param' => http_build_query(['length' => 8]),
            'header' => $header,
        ];

        $captchaResponse = $this->curlRequest($captchaParams);
        $captchaData = json_decode($captchaResponse, true);

        if (!isset($captchaData['result'])) {
            throw new PaymentException('Get captcha error', 150180212);
        }

        if ($captchaData['result'] !== 'ok') {
            if (isset($captchaData['code']) && !isset($captchaData['msg'])) {
                throw new PaymentException($captchaData['msg'], $captchaData['code']);
            }

            throw new PaymentException('Get captcha fail', 150180213);
        }

        if (!isset($captchaData['ret']['captcha'])) {
            throw new PaymentException('No return captcha', 150180214);
        }
        $captcha = $captchaData['ret']['captcha'];

        // 訂單查詢
        $depositParams = [
            'method' => 'POST',
            'uri' => "/api/trade/v1/deposit/{$orderId}/inquire",
            'ip' => [$pinkIp['host']],
            'host' => $pinkHost,
            'param' => http_build_query(
                ['data' => openssl_encrypt(json_encode($this->trackingRequestData), 'des-cbc', $captcha, 0, $captcha)]
            ),
            'header' => $header,
            'timeout' => 30,
        ];

        // 取得支付參數
        $depositResponse = $this->curlRequest($depositParams);
        $depositData = json_decode($depositResponse, true);

        if (!isset($depositData['result'])) {
            throw new PaymentException('Get tracking params error', 1501802215);
        }

        if ($depositData['result'] !== 'ok') {
            if (isset($depositData['code']) && isset($depositData['msg'])) {
                throw new PaymentException($depositData['msg'], $depositData['code']);
            }

            throw new PaymentException('Get tracking params fail', 150180216);
        }

        if (!isset($depositData['ret'])) {
            throw new PaymentException('No tracking deposit data', 150180217);
        }

        $ret = $depositData['ret']['ret'];

        $this->payMethod = $ret['method'];
        $this->setQrcode($ret['qrcode']);
        $this->setHtml($ret['html']);

        $output = [
            'method' => $ret['method'],
            'post_url' => $ret['url'],
            'params' => $ret['data_params'],
        ];

        return $output;
    }

    /**
     * curl request 解密
     *
     * @param string $request
     * @return string
     */
    protected function curlRequestDecode($request)
    {
        return $request;
    }

    /**
     * curl response 解密
     *
     * @param string $response
     * @return string
     */
    protected function curlResponseDecode($response)
    {
        return $response;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
