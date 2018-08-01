<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * YZ大商城
 */
class YZ extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => '', // 支付類型，固定值
        'merchant_id' => '', // 商號
        'sign' => '', // 加密簽名
        'nonce_str' => '', // 隨機字串，帶入username
        'notify_url' => '', // 異步通知付款結果網址
        'order_no' => '', // 訂單編號
        'total_fee' => '', // 訂單金額，單位分
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'service' => 'paymentVendorId',
        'merchant_id' => 'number',
        'nonce_str' => 'username',
        'notify_url' => 'notify_url',
        'order_no' => 'orderId',
        'total_fee' => 'amount',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'merchant_id',
        'nonce_str',
        'notify_url',
        'order_no',
        'total_fee',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'service' => 1,
        'merchant_id' => 1,
        'nonce_str' => 1,
        'order_no' => 1,
        'total_fee' => 1,
        'out_trade_no' => 1,
        'is_paid' => 1,
        'notify_time' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => 'YZ_Quick', // 銀聯在線(快捷)
        '1088' => 'YZ_Quick', // 銀聯在線_手機支付(快捷)
        '1092' => 'YZ_Alipay_QR', // 支付寶_二維
        '1098' => 'YZ_Alipay_H5', // 支付寶_手機支付
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

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['service'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['service'] = $this->bankMap[$this->requestData['service']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['result']) || !isset($parseData['message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['result'] != 'success') {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if ($this->options['paymentVendorId'] == '1092') {
            $this->setQrcode($parseData['url']);

            return [];
        }

        // 銀聯在線
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $getUrl = [];
            preg_match('/action="([^"]+)/', htmlspecialchars_decode($parseData['url']), $getUrl);

            if (!isset($getUrl[1])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $out = [];
            $pattern = '/name="(.*)" value="([^"]*)"/U';
            preg_match_all($pattern, htmlspecialchars_decode($parseData['url']), $out);

            return [
                'post_url' => $getUrl[1],
                'params' => array_combine($out[1], $out[2])
            ];
        }

        return [
            'post_url' => $parseData['url'],
            'params' => [],
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

        $this->payResultVerify();

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['is_paid'] != 'true') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
