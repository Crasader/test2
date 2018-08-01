<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 金桔支付
 */
class JinJiePay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'mechno' => '', // 商戶號
        'orderip' => '', // ip
        'amount' => '', // 訂單金額，單位:分
        'body' => '', // 商品名稱，帶入username
        'notifyurl' => '', // 異步通知地址，不能帶任何參數
        'returl' => '', // 前台頁面轉跳地址，可空
        'orderno' => '', // 訂單號
        'payway' => '', // 支付方式
        'paytype' => '', // 支付類別
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mechno' => 'number',
        'orderip' => 'ip',
        'amount' => 'amount',
        'body' => 'username',
        'notifyurl' => 'notify_url',
        'returl' => 'notify_url',
        'orderno' => 'orderId',
        'paytype' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mechno',
        'orderip',
        'amount',
        'body',
        'notifyurl',
        'returl',
        'orderno',
        'payway',
        'paytype',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'status' => 1,
        'charset' => 0,
        'transactionid' => 0,
        'outtransactionid' => 0,
        'outorderno' => 1,
        'totalfee' => 1,
        'mchid' => 1,
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
        1090 => 'WECHAT_SCANPAY', // 微信_二維
        1092 => 'ALIPAY_SCAN_PAY', // 支付寶_二維
        1097 => 'WECHAT_H5PAY', // 微信_WAP
        1098 => 'ALIPAY_WAP', // 支付寶_WAP
        1103 => 'QQ_SCANPAY', // QQ_二維
        1104 => 'QQ_WAP', // QQ_WAP
    ];

    /**
     * 金桔支付類別對應的支付方式
     *
     * @var array
     */
    protected $typeMap = [
        1090 => 'WECHAT',
        1092 => 'ALIPAY',
        1097 => 'WECHAT',
        1098 => 'ALIPAY',
        1103 => 'QQ',
        1104 => 'QQ',
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['paytype'] = $this->bankMap[$this->options['paymentVendorId']];
        $this->requestData['payway'] = $this->typeMap[$this->options['paymentVendorId']];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);

        // 設定加密簽名
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != 'null') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單處理中
        if ($this->options['status'] == '1') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['status'] != '100') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outorderno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['totalfee'] != round($entry['amount'] * 100)) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
