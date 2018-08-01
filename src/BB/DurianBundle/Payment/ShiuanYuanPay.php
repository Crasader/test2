<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 軒轅支付
 */
class ShiuanYuanPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0.0', // 版本號
        'merId' => '', // 商號
        'merOrderNo' => '', // 訂單號
        'orderAmt' => '', // 支付金額，單位:元，精確到小數後兩位
        'payPlat' => '', // 支付平台
        'notifyUrl' => '', // 異步通知網址
        'callbackUrl' => '', // 同步通知網址，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'merOrderNo' => 'orderId',
        'orderAmt' => 'amount',
        'payPlat' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'merId',
        'merOrderNo',
        'orderAmt',
        'payPlat',
        'notifyUrl',
        'callbackUrl',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'merId' => 1,
        'merOrderNo' => 1,
        'payNo' => 1,
        'payStatus' => 1,
        'payDate' => 1,
        'payTime' => 1,
        'orderAmt' => 1,
        'realAmt' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'wxpay', // 微信_二維
        '1092' => 'alipay', // 支付寶_二維
    ];

    /**
     * Payment 專案表單提交的 method
     *
     * @var string
     */
    protected $payMethod = 'GET';

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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['payPlat'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['payPlat'] = $this->bankMap[$this->requestData['payPlat']];
        $this->requestData['orderAmt'] = sprintf('%.2f', $this->requestData['orderAmt']);

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        return ['param' => json_encode($this->requestData)];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!in_array($this->options['payStatus'], ['S', 'B'])) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmt'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $paymentKey) {
            if (array_key_exists($paymentKey, $this->requestData) && trim($this->requestData[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->requestData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
