<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 瞬付
 */
class ShunFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merNo' => '', // 商戶號
        'payNetway' => '', // 支付方式
        'random' => '', // 隨機數，最多4位
        'orderNo' => '', // 訂單號
        'amount' => '', // 金額，單位：分
        'goodsInfo' => '', // 商品信息(帶入username方便業主比對)
        'callBackUrl' => '', // 異步通知地址
        'callBackViewUrl' => '', // 回顯地址
        'clientIP' => '', // 客戶IP
        'sign' => '', // 簽名，字母大寫
        'scanType' => '', // 付款模式，條碼用參數
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'payNetway' => 'paymentVendorId',
        'orderNo' => 'orderId',
        'amount' => 'amount',
        'goodsInfo' => 'username',
        'callBackUrl' => 'notify_url',
        'callBackViewUrl' => 'notify_url',
        'clientIP' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merNo',
        'payNetway',
        'random',
        'orderNo',
        'amount',
        'goodsInfo',
        'callBackUrl',
        'callBackViewUrl',
        'clientIP',
        'scanType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merNo' => 1,
        'payNetway' => 1,
        'orderNo' => 1,
        'amount' => 1,
        'goodsName' => 1,
        'resultCode' => 1,
        'payDate' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '000000';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WX', // 微信
        '1092' => 'ZFB', // 支付寶_二維
        '1097' => 'WX_WAP', // 微信_手機支付
        '1098' => 'ZFB_WAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQ_WAP', // QQ_手機支付
        '1107' => 'JDQB', // 京東錢包_二維
        '1108' => 'JDQB_WAP', // 京東錢包_手機支付
        '1109' => 'BAIDU', // 百度_二維
        '1110' => 'BAIDU_WAP', // 百度_手機支付
        '1111' => 'YL', // 銀聯錢包_二維
        '1115' => 'WX_VERSA_SCAN', // 微信支付_條碼
        '1116' => 'ZFB_VERSA_SCAN', // 支付寶_條碼
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['payNetway'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['random'] = strval(rand(0, 9999));
        $this->requestData['amount'] = strval(round($this->requestData['amount'] * 100));
        $this->requestData['payNetway'] = $this->bankMap[$this->requestData['payNetway']];

        // 調整uri、port以及提交網址
        $postUrl = 'payment.http.trade.' . $this->options['verify_url'];
        $uri = '/api/pay.action';
        $port = '8080';

        // 支付寶條碼、微信條碼額外的參數設定
        if (in_array($this->options['paymentVendorId'], [1115, 1116])) {
            $this->requestData['scanType'] = 'Page';
            $postUrl = 'payment.http.cs.' . $this->options['verify_url'];
            $uri = '/api/preScanPay.action';
            $port = '9090';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $postUrl,
            'param' => 'data=' . json_encode($this->requestData, JSON_UNESCAPED_SLASHES),
            'header' => ['Port' => $port],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['resultCode']) || !isset($parseData['resultMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['resultCode'] !== '00') {
            throw new PaymentConnectionException($parseData['resultMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['qrcodeInfo'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104, 1108, 1110])) {
            $parseResult = $this->parseUrl(urldecode($parseData['qrcodeInfo']));

            // 轉字串編碼
            foreach ($parseResult['params'] as $key => $param) {
                $parseResult['params'][$key] = iconv('gb2312', 'utf-8', urldecode($param));
            }

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $parseResult['url'],
                'params' => $parseResult['params'],
            ];
        }

        $this->setQrcode($parseData['qrcodeInfo']);

        return [];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData) . $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['resultCode'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES) . $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}
