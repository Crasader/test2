<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 蜜蜂支付
 */
class BeePay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_code' => '', // 商戶號
        'merchant_order_no' => '', // 商戶訂單號
        'merchant_goods' => '', // 商品名稱，設定username方便業主比對
        'merchant_amount' => '', // 金額，單位：元，精確到小數點後兩位
        'gateway' => '', // 支付網關
        'urlcall' => '', // 異步通知網址
        'urlback' => '', // 同步通知網址
        'merchant_sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_code' => 'number',
        'merchant_order_no' => 'orderId',
        'merchant_goods' => 'username',
        'merchant_amount' => 'amount',
        'gateway' => 'paymentVendorId',
        'urlcall' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_code',
        'merchant_order_no',
        'merchant_goods',
        'merchant_amount',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_code' => 1,
        'merchant_order_no' => 1,
        'merchant_amount' => 1,
        'merchant_amount_orig' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => 'wechat', // 微信_二維
        1092 => 'alipay', // 支付寶_二維
        1097 => 'wechat_wap', // 微信_手機支付
        1098 => 'alipay_wap', // 支付寶_手機支付
        1102 => 'bank', // 網銀收銀檯
        1103 => 'qq', // QQ_二維
        1104 => 'qq_wap', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['gateway'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['gateway'] = $this->bankMap[$this->requestData['gateway']];
        $this->requestData['merchant_amount'] = sprintf('%.2f', $this->requestData['merchant_amount']);

        // 設定加密簽名
        $this->requestData['merchant_sign'] = $this->encode();

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['merchant_md5'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['merchant_sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['merchant_sign'] != base64_encode(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['merchant_order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['merchant_amount_orig'] != round($entry['amount'])) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['merchant_md5'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return base64_encode(md5($encodeStr));
    }
}
