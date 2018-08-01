<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 速銀支付
 */
class SuYinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'body' => '', // 商品的具體描述
        'buyerEmail' => '', // 買家Email，可空
        'charset' => 'UTF-8', // 參數編碼字符集
        'defaultbank' => '', // 網銀代碼
        'isApp' => 'web', // 接入方式 (web、app、H5)
        'merchantId' => '', // 支付平台分配的商戶ID
        'notifyUrl' => '', // 異步通知地址
        'orderNo' => '', // 商戶訂單號
        'paymentType' => '1', // 支付類型，固定值:1
        'paymethod' => 'directPay', // 直連模式:directPay
        'returnUrl' => '', // 同步通知網址
        'riskItem' => '', // 風控字段，默認為空
        'service' => 'online_pay', // 固定值:online_pay
        'title' => '', // 商品的名稱
        'totalFee' => '', // 訂單金額，單位元 (小數後兩位)
        'signType' => 'SHA', // 簽名方式
        'sign' => '', // 加簽結果
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

        // 從內部給定值到參數
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

        // 調整QQ手機支付提交參數
        if ($this->options['paymentVendorId'] == 1104) {
            $this->requestData['isApp'] = 'app';
        }

        // 調整京東手機支付提交參數
        if ($this->options['paymentVendorId'] == 1108) {
            $this->requestData['isApp'] = 'H5';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // QQ手機支付
        if ($this->options['paymentVendorId'] == 1104) {
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

            // 取得跳轉網址
            $urlData = $this->parseUrl($parseData['codeUrl']);

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $urlData['url'],
                'params' => $urlData['params'],
            ];
        }

        // 調整提交網址
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

        if ($this->options['sign'] != strtoupper(sha1($encodeStr))) {
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
