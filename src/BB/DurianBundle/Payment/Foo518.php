<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 518foo支付
 */
class Foo518 extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'body' => '', // 商品具體描述，不可空白
        'buyerEmail' => '', // 買家Email，非必填
        'charset' => 'UTF-8', // 字符集
        'defaultbank' => '', // 銀行代碼
        'isApp' => 'web', // 接入方式，WEB接入:web
        'merchantId' => '', // 商戶號
        'notifyUrl' => '', // 異步通知網址
        'orderNo' => '', // 商戶訂單號
        'paymentType' => '1', // 支付類型，固定值:1
        'paymethod' => 'directPay', // 支付方式，直連模式:directPay
        'returnUrl' => '', // 同步通知網址
        'riskItem' => '', // 風控字段，默認為空
        'service' => 'online_pay', // 固定值
        'title' => '', // 商品名稱，不可空白
        'totalFee' => '', // 訂單金額，單位元，精確到小數後兩位
        'signType' => 'SHA', // 簽名方式，固定值
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'body' => 'orderId',
        'defaultbank' => 'paymentVendorId',
        'merchantId' => 'number',
        'notifyUrl' => 'notify_url',
        'orderNo' => 'orderId',
        'returnUrl' => 'notify_url',
        'title' => 'orderId',
        'totalFee' => 'amount',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'body',
        'buyerEmail',
        'charset',
        'defaultbank',
        'isApp',
        'merchantId',
        'notifyUrl',
        'orderNo',
        'paymentType',
        'paymethod',
        'returnUrl',
        'riskItem',
        'service',
        'title',
        'totalFee',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'body' => 1,
        'buyer_email' => 0,
        'buyer_id' => 0,
        'discount' => 0,
        'ext_param1' => 0,
        'ext_param2' => 0,
        'gmt_create' => 1,
        'gmt_logistics_modify' => 1,
        'gmt_payment' => 1,
        'is_success' => 1,
        'is_total_fee_adjust' => 0,
        'notify_id' => 1,
        'notify_time' => 1,
        'notify_type' => 1,
        'order_no' => 1,
        'payment_type' => 1,
        'price' => 0,
        'quantity' => 0,
        'seller_actions' => 1,
        'seller_email' => 1,
        'seller_id' => 1,
        'title' => 1,
        'total_fee' => 1,
        'trade_no' => 1,
        'trade_status' => 1,
        'use_coupon' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1103' => 'QQPAY', // QQ_二維
        '1104' => 'QQPAY', // QQ_手機支付
        '1108' => 'JDPAY', // 京東_手機支付
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['defaultbank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['defaultbank'] = $this->bankMap[$this->requestData['defaultbank']];
        $this->requestData['totalFee'] = sprintf('%.2f', $this->requestData['totalFee']);

        // QQ二維與QQ手機支付
        if (in_array($this->options['paymentVendorId'], [1103, 1104])) {
            $this->requestData['isApp'] = 'app';
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/payment/v1/order/' . $this->requestData['merchantId'] . '-' . $this->requestData['orderNo'],
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['respCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != 'S0001' && isset($parseData['respMessage'])) {
                throw new PaymentConnectionException($parseData['respMessage'], 180130, $this->getEntryId());
            }

            if ($parseData['respCode'] != 'S0001') {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if (!isset($parseData['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // QQ手機支付
            if ($this->options['paymentVendorId'] == 1104) {
                $urlData = $this->parseUrl($parseData['codeUrl']);

                // Form使用GET才能正常跳轉
                $this->payMethod = 'GET';

                return [
                    'post_url' => $urlData['url'],
                    'params' => $urlData['params'],
                ];
            }

            $this->setQrcode($parseData['codeUrl']);

            return [];
        }

        // 京東手機支付
        if ($this->options['paymentVendorId'] = 1108) {
            $this->requestData['isApp'] = 'H5';
        }

        $this->requestData['sign'] = $this->encode();

        // 設定提交網址，網址末端需串上商號-單號
        $postUrl = $this->options['postUrl'] . $this->requestData['merchantId'] . '-' . $this->requestData['orderNo'];

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], sha1($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] !== 'TRADE_FINISHED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != $entry['amount']) {
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
            if ($this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(sha1($encodeStr));
    }
}
