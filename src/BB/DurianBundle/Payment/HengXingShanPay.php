<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 恒星闪付
 */
class HengXingShanPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'charset' => 'UTF-8', // 參數字符集編碼
        'version' => '1.0', // 版本號，固定值
        'businessType' => '', // 交易業務類型
        'merchantId' => '', // 商戶號
        'orderId' => '', // 商戶訂單號
        'tranTime' => '', // 交易日期，格式：YmdHis
        'tranAmt' => '', // 交易金額
        'backNotifyUrl' => '', // 異步通知地址
        'signType' => 'md5', // 簽名類型
        'signData' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'businessType' => 'paymentVendorId',
        'merchantId' => 'number',
        'orderId' => 'orderId',
        'tranTime' => 'orderCreateDate',
        'tranAmt' => 'amount',
        'backNotifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'charset',
        'version',
        'businessType',
        'merchantId',
        'orderId',
        'tranTime',
        'tranAmt',
        'backNotifyUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'charset' => 1,
        'version' => 1,
        'merchantId' => 1,
        'orderId' => 1,
        'payOrderId' => 1,
        'payType' => 1,
        'tranAmt' => 1,
        'orderSts' => 1,
        'tranTime' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => 'unionQuickH5', // 銀聯在線
        '1088' => 'unionQuickH5', // 銀聯在線_手機支付
        '1092' => 'aliPayQR', // 支付寶_二维
        '1103' => 'tencentQQ', // QQ_二维
        '1104' => 'tencentQQH5', // QQ_手機支付
        '1111' => 'unionPayQR', // 銀聯_二维
        '1121' => 'qqrDynamicQR', // 微信_快捷
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['businessType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['businessType'] = $this->bankMap[$this->requestData['businessType']];
        $this->requestData['tranAmt'] = strval(sprintf('%.2f', $this->requestData['tranAmt']));
        $createAt = new \Datetime($this->requestData['tranTime']);
        $this->requestData['tranTime'] = $createAt->format('YmdHis');

        $this->requestData['signData'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 調整二維提交網址
        $uri = '/mpsGate/qrmpsTransaction';

        // 調整手機支付、銀聯在線提交網址
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1104])) {
            $uri = '/mpsGate/h5mpsTransaction';
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => base64_encode(json_encode($this->requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] !== '00') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        // 銀聯在線、銀聯在線手機支付、QQ手機支付
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1104])) {
            if (!isset($parseData['H5Url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $urlData = $this->parseUrl($parseData['H5Url']);

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $urlData['url'],
                'params' => $urlData['params'],
            ];
        }

        if (!isset($parseData['codeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['codeUrl']);

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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signData'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderSts'] != 'PD') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tranAmt'] != $entry['amount']) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}
