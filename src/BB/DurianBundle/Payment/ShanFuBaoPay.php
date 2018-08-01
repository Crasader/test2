<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 閃付寶支付
 */
class ShanFuBaoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'return_type' => 'html', // 返回數據類型
        'api_code' => '', // 商號
        'is_type' => '', // 支付類型
        'price' => '', // 金額，單位元，精確到小數點後2位
        'order_id' => '', // 訂單號
        'time' => '', // 時間戳
        'mark' => '', // 描述，不可為空
        'return_url' => '', // 同步通知地址，不可為空
        'notify_url' => '', // 異步通知地址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'api_code' => 'number',
        'is_type' => 'paymentVendorId',
        'price' => 'amount',
        'order_id' => 'orderId',
        'time' => 'orderCreateDate',
        'mark' => 'orderId',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'return_type',
        'api_code',
        'is_type',
        'price',
        'order_id',
        'time',
        'mark',
        'return_url',
        'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'paysapi_id' => 1,
        'order_id' => 1,
        'is_type' => 1,
        'price' => 1,
        'real_price' => 1,
        'mark' => 1,
        'code' => 1,
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
        1092 => 'alipay5', // 支付寶_二維
        1098 => 'alipay5', // 支付寶_手機支付
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
        if (!array_key_exists($this->requestData['is_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['is_type'] = $this->bankMap[$this->requestData['is_type']];
        $this->requestData['price'] = sprintf('%.2f', $this->requestData['price']);
        $date = new \DateTime($this->requestData['time']);
        $this->requestData['time'] = $date->getTimestamp();

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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 未返回商號因此補回商號
        $encodeData = ['api_code' => $entry['merchant_number']];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['code'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['price'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if (trim($this->requestData[$index]) != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
