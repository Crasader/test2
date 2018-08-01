<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * AustPay二代
 */
class AustPay2 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'app_id' => '', // APPID(商號)
        'sign' => '', // 簽名
        'type' => '3', // 返回參數類型。2:返回二維碼, 3:返回跳轉網址
        'create_time' => '', // 請求時間 YmdHis
        'out_trade_no' => '', // 商戶訂單號
        'subject' => '', // 訂單標題
        'total_amount' => '', // 支付金額
        'pay_type' => '', // 支付方式
        'body' => '', // 訂單內容
        'return_url' => '', // 同步通知網址
        'notify_url' => '', // 異步通知網址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'app_id' => 'number',
        'create_time' => 'orderCreateDate',
        'out_trade_no' => 'orderId',
        'subject' => 'username',
        'total_amount' => 'amount',
        'body' => 'username',
        'pay_type' => 'paymentVendorId',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'app_id',
        'create_time',
        'out_trade_no',
        'subject',
        'total_amount',
        'pay_type',
        'body',
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
        'subject' => 1,
        'body' => 0,
        'trade_status' => 1,
        'total_amount' => 1,
        'sysd_time' => 1,
        'trade_time' => 1,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'notify_time' => 1,
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
        '278' => '80008', // 銀聯在線(快捷)
        '1088' => '80008', // 銀聯在線_手機支付(快捷)
        '1090' => '80002', // 微信_二維
        '1092' => '80001', // 支付寶_二維
        '1097' => '80009', // 微信_手機支付
        '1098' => '80010', // 支付寶_手機支付
        '1102' => '80003', // 網銀收銀台
        '1103' => '80004', // QQ錢包_二維
        '1107' => '80005', // 京東錢包_二維
        '1109' => '80006', // 百度錢包_二維
        '1111' => '80007', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['pay_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['create_time']);
        $this->requestData['create_time'] = $date->format('YmdHis');
        $this->requestData['total_amount'] = round($this->requestData['total_amount'] * 100);
        $this->requestData['pay_type'] = $this->bankMap[$this->requestData['pay_type']];

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
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);
        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA256)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (trim($this->options['trade_status']) !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_amount'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            if (trim($this->requestData[$key]) !== '') {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA256)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
