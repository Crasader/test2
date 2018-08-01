<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 力大支付
 */
class LiDa extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'outTradeNo' => '', // 訂單號
        'money' => '', // 金額，單位：分
        'type' => '', // 付款類型
        'body' => '', // 商品描述，必填
        'detail' => '', // 商品詳情，必填
        'notifyUrl' => '', // 異步通知網址
        'successUrl' => '', // 同步通知網址
        'productId' => '', // 商品ID，必填
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'outTradeNo' => 'orderId',
        'money' => 'amount',
        'body' => 'orderId',
        'detail' => 'orderId',
        'notifyUrl' => 'notify_url',
        'successUrl' => 'notify_url',
        'productId' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantNo',
        'nonce',
        'timestamp',
        'token',
    ];

    /**
     * 返回時需要驗證的參數
     *
     * @var array
     */
    protected $returnParams = [
        'outTradeNo',
        'money',
        'success',
    ];

    /**
     * 返回驗簽時需要加密的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo',
        'no',
        'nonce',
        'timestamp',
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 取得token時送給支付平台的參數
     *
     * @var array
     */
    private $tokenParams = [
        'merchantNo' => '', // 商號
        'key' => '', // 商號密鑰
        'nonce' => '', // 隨機字串
        'timestamp' => '', // 時間戳，格式:YmdHis
        'sign' => '', // 簽名
        'token' => '', // 令牌，可空
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1098' => '', // 支付寶_手機支付
        '1104' => '', // QQ_手機支付
    ];

    /**
     * 支付平台支援的銀行對應uri
     *
     * @var array
     */
    protected $uriMap = [
        '1098' => '/open/v1/order/alipayWapPay', // 支付寶_手機支付
        '1104' => '/open/v1/order/qqScan', // QQ_手機支付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 額外的參數設定
        $this->requestData['money'] = round($this->requestData['money'] * 100);

        // QQ固定T0
        $this->requestData['type'] = 'T0';

        // 支付寶固定T1
        if ($this->options['paymentVendorId'] == '1098') {
            $this->requestData['type'] = 'T1';
        }

        $param = [
            'accessToken' => $this->getAccessToken(),
            'param' => $this->requestData,
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => $this->uriMap[$this->options['paymentVendorId']],
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($param),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 檢查返回參數
        if (!isset($parseData['success'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!$parseData['success'] && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!$parseData['success'] || !isset($parseData['value'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $urlData = $this->parseUrl($parseData['value']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證返回參數
        foreach (array_merge($this->returnParams, $this->decodeParams) as $paymentKey) {
            if (!isset($this->options[$paymentKey])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        // 產生加密串
        $encodeData = [];

        foreach ($this->decodeParams as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!$this->options['success']) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] !== $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] !== trim(round($entry['amount'] * 100))) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->tokenParams) && trim($this->tokenParams[$index]) !== '') {
                $encodeData[$index] = $this->tokenParams[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->tokenParams['key'];
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 登入取得token
     *
     * @return string
     */
    private function getAccessToken()
    {
        // 登入參數設定
        $this->tokenParams['merchantNo'] = $this->options['number'];
        $this->tokenParams['key'] = $this->privateKey;
        $this->tokenParams['nonce'] = md5(uniqid(rand(), true));
        $date = new \DateTime('now');
        $this->tokenParams['timestamp'] = $date->format('YmdHis');

        // 設定加密串
        $this->tokenParams['sign'] = $this->encode();

        // 取得token
        $curlParam = [
            'method' => 'POST',
            'uri' => '/open/v1/getAccessToken/merchant',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->tokenParams),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 檢查返回參數
        if (!isset($parseData['success'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!$parseData['success'] && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!$parseData['success'] || !isset($parseData['value']['accessToken'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return $parseData['value']['accessToken'];
    }
}
