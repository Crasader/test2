<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯通金融
 */
class HuiTong extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'notify_url' => '', // 異步通知地址
        'return_url' => '', // 同步跳轉通知地址
        'pay_type' => '1', // 支付方式。1:網銀支付、2:微信_二維、3:支付寶_二維、5:QQ錢包_二維
        'bank_code' => '', // 銀行編碼
        'merchant_code' => '', // 商戶號
        'order_no' => '', // 商戶訂單號
        'order_amount' => '', // 商戶訂單號金額，單位:元
        'order_time' => '', // 商戶訂單時間，Y-m-d H:i:s
        'req_referer' => '', // 來路域名
        'customer_ip' => '', // 消費者ip
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'notify_url' => 'notify_url',
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
        'notify_url',
        'return_url',
        'pay_type',
        'bank_code',
        'merchant_code',
        'order_no',
        'order_amount',
        'order_time',
        'req_referer',
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
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
        'notify_type' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCOM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMBC', // 招商銀行
        6 => 'CMBCS', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'ECITIC', // 中信銀行
        12 => 'CEBBANK', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'CGB', // 廣東發展銀行
        15 => 'PINGAN', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'BOS', // 上海銀行
        1090 => '2', // 微信支付
        1092 => '3', // 支付寶二維
        1103 => '5', // QQ二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);

        //　時間格式設定
        $createAt = new \Datetime($this->requestData['order_time']);
        $this->requestData['order_time'] = $createAt->format('Y-m-d H:i:s');

        // 來路域名設定
        $reqReferer = parse_url($this->requestData['notify_url']);

        // 未產生host則噴例外
        if (!isset($reqReferer['host']) || $reqReferer['host'] == '') {
            throw new PaymentException('Invalid notify_url', 180146);
        }

        $this->requestData['req_referer'] = $reqReferer['host'];

        // 二維參數設定
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1103'])) {
            $this->requestData['pay_type'] = $this->bankMap[$this->options['paymentVendorId']];
        }

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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 驗簽
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[$paymentKey] = $this->options[$paymentKey];
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] == 'paying') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
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

        foreach ($this->encodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->requestData[$paymentKey];
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}
