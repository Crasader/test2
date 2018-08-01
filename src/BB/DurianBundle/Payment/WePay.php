<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * WePay
 */
class WePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'BuCode' => '', // 商户號
        'OrderId' => '', // 商戶訂單號
        'PayChannel' => '', // 用戶充值管道
        'OrderAccount' => '', // 商户方UID
        'Amount' => '', // 加值金額
        'Sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'BuCode' => 'number',
        'OrderId' => 'orderId',
        'PayChannel' => 'paymentVendorId',
        'Amount' => 'amount',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'BuCode',
        'OrderId',
        'PayChannel',
        'OrderAccount',
        'Amount',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'BuCode' => 1,
        'TransId' => 1,
        'Amount' => 1,
        'Status' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"status":true,"err_msg":"success"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WeChat', // 微信
        '1092' => 'AliPay', // 支付寶
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
        if (!array_key_exists($this->requestData['PayChannel'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['PayChannel'] = $this->bankMap[$this->requestData['PayChannel']];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定支付平台需要的加密串
        $this->requestData['Sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/deposit/apply',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => [
                'Content-Type' => 'charset=utf-8;application/json',
                'Lang' => 'zh-cn',
            ],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!$parseData['status'] && isset($parseData['err_msg'])) {
            throw new PaymentConnectionException($parseData['err_msg'], 180130, $this->getEntryId());
        }

        if (!$parseData['status'] || !isset($parseData['data']['redirectURL'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 擷取提交網址
        $getUrl = [];
        preg_match('/action="([^"]+)/', $parseData['data']['redirectURL'], $getUrl);

        if (!isset($getUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 擷取支付參數
        $out = [];
        $pattern = '/<Input.*Name=\'([^\']+)\'.*value=\'([^\']*)\'/U';

        if (!preg_match_all($pattern, $parseData['data']['redirectURL'], $out)) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' => $getUrl[1],
            'params' => array_combine($out[1], $out[2]),
        ];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['Key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Status'] != 'true') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 返回訂單號参数是由商號+訂單號組成
        $orderId = preg_replace("/^{$this->options['BuCode']}/", '', $this->options['TransId']);

        if ($orderId != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != $entry['amount']) {
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
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['Key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}
