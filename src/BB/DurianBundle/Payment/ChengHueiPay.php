<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 誠匯支付
 */
class ChengHueiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'payTypeKey' => '', // 支付類型
        'tradeNo' => '', // 交易號
        'outTradeNo' => '', // 訂單號
        'body' => '', // 商品描述，設定username方便業主比對
        'totalFee' => '',  // 金額，單位：分
        'requestIp' => '', // 終端IP
        'nonceStr' => '', // 隨機字符串
        'payIdentity' => '', // 支付用戶標識
        'merchNo' => '', // 商戶號，非必填
        'notifyUrl' => '', // 異步通知網址
        'returnUrl' => '', // 同步通知網址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'payTypeKey' => 'paymentVendorId',
        'tradeNo' => 'number',
        'outTradeNo' => 'orderId',
        'body' => 'username',
        'totalFee' => 'amount',
        'requestIp' => 'ip',
        'payIdentity' => 'username',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'payTypeKey',
        'tradeNo',
        'outTradeNo',
        'body',
        'totalFee',
        'requestIp',
        'nonceStr',
        'payIdentity',
        'notifyUrl',
        'returnUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'result_code' => 1,
        'err_code' => 0,
        'err_msg' => 0,
        'merch_no' => 1,
        'nonce_str' => 1,
        'trade_type' => 1,
        'trade_state' => 1,
        'transaction_id' => 1,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'fee_type' => 1,
        'time_end' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1088' => 'pay.union.h5.online', // 銀聯在線_手機支付
        '1090' => 'pay.weixin.native', // 支付寶_二維
        '1092' => 'pay.alipay.native', // 支付寶_二維
        '1097' => 'pay.wx.h5', // 微信_手機支付
        '1103' => 'pay.qq.native', // QQ_二維
        '1104' => 'pay.qq.h5', // QQ_手機支付
        '1107' => 'pay.jd.scan', // 京東錢包_二維
        '1108' => 'pay.jd.h5', // 京東錢包_手機支付
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['payTypeKey'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['totalFee'] = round($this->requestData['totalFee'] * 100);
        $this->requestData['payTypeKey'] = $this->bankMap[$this->requestData['payTypeKey']];
        $this->requestData['nonceStr'] = md5(uniqid(rand(), true));

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/dopay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json;charset=utf-8'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['returnCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['returnCode'] != 'SUCCESS' && !isset($parseData['errCodeDes'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['returnCode'] != 'SUCCESS') {
            throw new PaymentConnectionException($parseData['errCodeDes'], 180130, $this->getEntryId());
        }

        // 手機支付
        if (in_array($this->options['paymentVendorId'], ['1088', '1097', '1104', '1108'])) {
            if (!isset($parseData['redirectUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($this->options['paymentVendorId'] == '1088') {
                // 提交網址需解析參數
                return $this->parsePostUrl($parseData['redirectUrl']);
            }

            return [
                'post_url' => $parseData['redirectUrl'],
                'params' => [],
            ];
        }

        // 二維支付
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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result_code'] != 'SUCCESS' || $this->options['trade_state'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            if (trim($this->requestData[$key]) !== '') {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 解析提交網址
     *
     * @var string
     */
    private function parsePostUrl($url)
    {
        $parseUrl = parse_url($url);

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

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $postUrl,
            'params' => $params,
        ];
    }
}
