<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 福卡通
 */
class FuKaTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'input_charset' => 'UTF-8', // 參數字符集編碼
        'inform_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址
        'pay_type' => '1', // 支付方式
        'bank_code' => '', // 銀行編碼，非網銀可空
        'merchant_code' => '', // 商號
        'order_no' => '', // 訂單號
        'order_amount' => '', // 訂單金額，精確到小數後兩位，需用AES加密
        'order_time' => '', // 訂單時間，yyyy-MM-dd HH:mm:ss
        'req_referer' => '', // 來路域名(可空)
        'customer_ip' => '', // 消費者ip
        'return_params' => '', // 回傳參數(可空)
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'inform_url' => 'notify_url',
        'return_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
        'merchant_code' => 'number',
        'order_no' => 'orderId',
        'order_amount' => 'amount',
        'order_time' => 'orderCreateDate',
        'customer_ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'input_charset',
        'inform_url',
        'return_url',
        'pay_type',
        'bank_code',
        'merchant_code',
        'order_no',
        'order_amount',
        'order_time',
        'customer_ip',
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
        'order_no' => 1,
        'order_amount' => 1,
        'order_time' => 1,
        'trade_status' => 1,
        'trade_no' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMBC', // 招商銀行
        '6' => 'CMBCS', // 中國民生銀行
        '8' => 'SPDB',   //上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBBANK', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '1090' => '2', // 微信支付_二維
        '1092' => '3', // 支付寶_二維
        '1097' => '7', // 微信_手機支付
        '1103' => '5', // QQ_二维
        '1107' => '6', // 京東_二維
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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 支付金額AES加密
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $this->requestData['order_amount'] = $this->aesEncode($this->requestData['order_amount']);

        // 額外的參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $createAt = new \Datetime($this->requestData['order_time']);
        $this->requestData['order_time'] = $createAt->format('Y-m-d H:i:s');

        // 非網銀支付方式參數設定
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1097', '1103', '1107'])) {
            $this->requestData['pay_type'] = $this->requestData['bank_code'];
            unset($this->requestData['bank_code']);
        }

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

        $encodeData = [];

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

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單未支付
        if ($this->options['trade_status'] == 'paying') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單未成功
        if ($this->options['trade_status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * AES加密
     *
     * @param string $amount
     *
     * @return string
     */
    protected function aesEncode($amount)
    {
        $length = strlen($this->privateKey);

        if ($length % 2 != 0 || !ctype_xdigit($this->privateKey)) {
            throw new PaymentException('Invalid Private Key', 150180208);
        }

        $res = openssl_encrypt($amount, 'AES-128-ECB', hex2bin($this->privateKey), OPENSSL_RAW_DATA);

        return strtoupper(bin2hex($res));
    }
}
