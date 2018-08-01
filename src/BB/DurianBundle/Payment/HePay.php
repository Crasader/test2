<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 和支付
 */
class HePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'seller_id' => '', // 商號
        'order_type' => '', // 訂單類型
        'pay_body' => '', // 商品描述，設定username方便業主比對
        'out_trade_no' => '', // 訂單號
        'total_fee' => '', // 金額，單位：分
        'notify_url' => '', // 回調地址, 不能串參數
        'spbill_create_ip' => '', // 訂單創建ip
        'spbill_times' => '', // 時間戳
        'noncestr' => '', // 隨機字符串，長度小於32，設定username方便業主比對
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'seller_id' => 'number',
        'order_type' => 'paymentVendorId',
        'pay_body' => 'username',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'notify_url' => 'notify_url',
        'spbill_create_ip' => 'ip',
        'spbill_times' => 'orderCreateDate',
        'noncestr' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'seller_id',
        'order_type',
        'pay_body',
        'out_trade_no',
        'total_fee',
        'notify_url',
        'spbill_create_ip',
        'spbill_times',
        'noncestr',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'opay_status' => 1,
        'order_type' => 1,
        'out_trade_no' => 1,
        'pay_status' => 1,
        'seller_id' => 1,
        'total_fee' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => '2703', // 銀聯在線
        '1097' => '2706', // 微信_手機支付
        '1102' => '2704', // 網銀收銀台
        '1103' => '2705', // QQ_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['order_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['order_type'] = $this->bankMap[$this->requestData['order_type']];
        $date = new \DateTime($this->requestData['spbill_times']);
        $this->requestData['spbill_times'] = $date->getTimestamp();

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/wbsp/unifiedorder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'text/html; charset=utf-8'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['state']) || !isset($parseData['return_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // state 狀態不為0的時候，是否有回傳錯誤訊息，沒有則噴錯
        if ($parseData['state'] != 0 && !isset($parseData['return_msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['state'] != 0) {
            throw new PaymentConnectionException($parseData['return_msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['pay_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二维支付
        if ($this->options['paymentVendorId'] == 1103) {
            $this->setQrcode($parseData['pay_url']);

            return [];
        }

        return $this->getPayData($parseData);
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_status'] != '1') {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 取得支付參數
     *
     * @return array
     */
    private function getPayData($parseData)
    {
        $parseUrl = parse_url($parseData['pay_url']);

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

        $param = [];

        if (isset($parseUrl['query'])) {
            parse_str($parseUrl['query'], $param);
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
            'params' => $param,
        ];
    }
}
