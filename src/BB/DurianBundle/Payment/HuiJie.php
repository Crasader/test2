<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯捷支付
 */
class HuiJie extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merNo' => '', // 商戶號
        'amount' => '', // 金額，單位：元
        'notifyUrl' => '', // 異步通知地址
        'returnUrl' => '', // 頁面通知地址
        'orderNo' => '', // 商戶訂單號
        'payType' => '', // 支付方式
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'amount' => 'amount',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'orderNo' => 'orderId',
        'payType' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merNo',
        'merSecret',
        'orderNo',
        'amount',
        'payType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'status' => 1,
        'payType' => 1,
        'orderNo' => 1,
        'orderStatus' => 1,
        'orderAmount' => 1,
        'payoverTime' => 1,
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
        '1090' => 'WEIXIN', // 微信支付_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1097' => 'WXH5', // 微信_手機支付
        '1098' => 'ALIH5', // 支付寶_手機支付
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

        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];

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
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['merSecret'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != $entry['amount']) {
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
            if ($index == 'merSecret') {
                $encodeData[$index] = $this->privateKey;

                continue;
            }

            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}
