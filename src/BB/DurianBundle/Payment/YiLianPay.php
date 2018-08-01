<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 億聯支付
 */
class YiLianPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'inputCharset' => '1', // 字符集，1:UTF-8
        'partnerId' => '', // 商號
        'signType' => '1', // 簽名類型，1:RSA
        'notifyUrl' => '', // 異步通知網址
        'returnUrl' => '', // 同步通知網址
        'orderNo' => '', // 商戶訂單號
        'orderAmount' => '', // 訂單金額，單位:分
        'orderCurrency' => '156', // 156:人民幣
        'orderDatetime' => '', // 訂單時間，格式YMDHIS
        'signMsg' => '', // 簽名
        'subject' => '', // 訂單備註，非必填
        'body' => '', // 訂單描述，非必填
        'extraCommonParam' => '', // 公用回傳參數，非必填
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partnerId' => 'number',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'orderDatetime' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'inputCharset',
        'partnerId',
        'notifyUrl',
        'returnUrl',
        'orderNo',
        'orderAmount',
        'orderCurrency',
        'orderDatetime',
        'subject',
        'body',
        'extraCommonParam',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'inputCharset' => 1,
        'partnerId' => 1,
        'paymentOrderId' => 1,
        'orderNo' => 1,
        'orderAmount' => 1,
        'orderDatetime' => 1,
        'payDatetime' => 1,
        'payResult' => 1,
        'returnDatetime' => 1,
        'extraCommonParam' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'wxcreate', // 微信_二維
        '1092' => 'alicreate', // 支付寶_二維
        '1103' => 'qqcreate', // QQ_二維
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
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $date = new \DateTime($this->requestData['orderDatetime']);
        $this->requestData['orderDatetime'] = $date->format('YmdHis');
        $url = $this->bankMap[$this->options['paymentVendorId']];

        // 設定支付平台需要的加密串
        $this->requestData['signMsg'] = $this->encode();

        // 調整網銀提交網址
        $postUrl = $this->options['postUrl'] . $url;

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
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (openssl_verify($encodeStr, base64_decode($this->options['signMsg']), $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
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
            if ($this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
