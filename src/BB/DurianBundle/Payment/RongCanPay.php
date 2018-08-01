<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 融燦支付
 */
class RongCanPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'charset' => 'UTF-8', // 編碼方式，固定值
        'merchantCode' => '', // 商號
        'orderNo' => '', // 訂單號
        'amount' => '', // 金額，單位分
        'channel' => 'BANK', // 支付通道，固定值
        'bankCode' => '', // 網銀直連銀行代碼
        'remark' => '', // 訂單描述，不可空
        'notifyUrl' => '', // 異步通知網址
        'returnUrl' => '', // 同步通知網址，可為空
        'extraReturnParam' => '', // 公用回傳參數，可為空
        'signType' => 'RSA', // 簽名方式
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'orderNo' => 'orderId',
        'amount' => 'amount',
        'bankCode' => 'paymentVendorId',
        'remark' => 'orderId',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'charset',
        'merchantCode',
        'orderNo',
        'amount',
        'channel',
        'bankCode',
        'remark',
        'notifyUrl',
        'returnUrl',
        'extraReturnParam',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantCode' => 1,
        'orderNo' => 1,
        'amount' => 1,
        'successAmt' => 1,
        'payOrderNo' => 1,
        'orderStatus' => 1,
        'extraReturnParam' => 1,
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
        '278' => 'UNION_PC', // 銀聯在線(快捷)
        '1088' => 'UNION_WAP', // 銀聯在線_手機支付(快捷)
        '1102' => 'CASHIER', // 網銀_收銀台
        '1111' => 'UNION_APP_QR', // 銀聯_二維
        '1114' => 'UNION_YMF_QR', // 一碼付
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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
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

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] != 'Success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['successAmt'] != round($entry['amount'] * 100)) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
