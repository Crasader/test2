<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 多元寶
 */
class DuoYuanBao extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'body' => '', // 商品具體描述，不可為空
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
        'title' => '', // 商品名稱，不可為空
        'totalFee' => '', // 訂單金額，單位元
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '15' => 'PAYH', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHBANK', // 上海銀行
        '1103' => 'QQPAY', // QQ_二維
        '1104' => 'QQPAY', // QQ_手機支付
        '1107' => 'JDPAY', // 京東錢包_二维
        '1108' => 'JDPAY', // 京東錢包_手機支付
        '1111' => 'UNIONQRPAY', // 銀聯錢包_二維
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

        // QQ二維支付、QQ手機支付、京東二維支付
        if (in_array($this->options['paymentVendorId'], [1103, 1104, 1107])) {
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

            if ($parseData['respCode'] != 'S0001' && !isset($parseData['respMessage'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != 'S0001') {
                throw new PaymentConnectionException($parseData['respMessage'], 180130, $this->getEntryId());
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

        // 京東手機支付調整提交參數
        if ($this->options['paymentVendorId'] == 1108) {
            $this->requestData['isApp'] = 'H5';
        }

        // 銀聯二維調整提交參數
        if ($this->options['paymentVendorId'] == 1111) {
            $this->requestData['isApp'] = 'app';
        }

        $this->requestData['sign'] = $this->encode();

        // 設定提交網址
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
            if (array_key_exists($paymentKey, $this->options)) {
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
