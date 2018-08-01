<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * POCO PAY
 */
class PoCoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner_id' => '', // 商戶簽約PID
        'service' => 'com.order.Unified.Pay', // 調用接口
        'sign_type' => 'RSA', // 簽名方式
        'rand_str' => '', // 隨機字串，必需32位
        'sign' => '', // 簽名
        'version' => 'v1', // 版本號，固定值:v1
        'merchant_no' => '', // 商戶號
        'merchant_order_sn' => '', // 訂單號
        'paychannel_type' => '', // 支付方式
        'trade_amount' => '', // 支付金額，單位:分
        'merchant_notify_url' => '', // 異步通知
        'ord_name' => '', // 訂單描述
        'interface_type' => '1', // 接口類型，1:裸接口(返回數據)，2:收銀台
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_no' => 'number',
        'merchant_order_sn' => 'orderId',
        'paychannel_type' => 'paymentVendorId',
        'trade_amount' => 'amount',
        'merchant_notify_url' => 'notify_url',
        'ord_name' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'partner_id',
        'service',
        'sign_type',
        'rand_str',
        'version',
        'merchant_no',
        'merchant_order_sn',
        'paychannel_type',
        'trade_amount',
        'merchant_notify_url',
        'ord_name',
        'interface_type',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_no' => 1,
        'merchant_order_sn' => 1,
        'order_sn' => 1,
        'pay_status' => 1,
        'trade_amount' => 1,
        'rand_str' => 1,
        'pay_time' => 1,
        'paychannel_type' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1103' => 'qq_qrcode', // QQ_二維
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['paychannel_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $merchantExtraValues = $this->getMerchantExtraValue(['partner_id']);
        $this->requestData['partner_id'] = $merchantExtraValues['partner_id'];

        $this->requestData['rand_str'] = md5($this->requestData['merchant_order_sn']);
        $this->requestData['paychannel_type'] = $this->bankMap[$this->requestData['paychannel_type']];
        $this->requestData['trade_amount'] = strval(round($this->requestData['trade_amount'] * 100));

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/v2',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            ],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);


        if (!isset($parseData['errcode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['errcode'] !== 0 && isset($parseData['msg'])) {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if ($parseData['errcode'] !== 0) {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['data']['out_pay_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['data']['out_pay_url']);

        return [];
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

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);
        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchant_order_sn'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['trade_amount'] != round($entry['amount'] * 100)) {
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
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
