<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易優通
 */
class YiYuTung extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_id' => '', // 商戶號
        'billno' => '', // 訂單號
        'amount' => '',  // 金額，單位：元
        'order_date' => '', // 訂單日期，格式YmdHis
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址
        'bank_code' => '', // 銀行編號
        'goods_name' => '', // 商品名稱，設定username方便業主比對
        'pay_type' => '1', // 支付類型，1:網銀
        'sign_type' => 'MD5', // 簽名類型
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_id' => 'number',
        'billno' => 'orderId',
        'amount' => 'amount',
        'order_date' => 'orderCreateDate',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
        'goods_name' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'amount',
        'billno',
        'goods_name',
        'merchant_id',
        'notify_url',
        'order_date',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'billno' => 1,
        'merchant_id' => 1,
        'order_date' => 1,
        'order_id' => 1,
        'success' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '219' => 'GZCB', //廣州銀行
        '1103' => 'QQPay', // QQ_二維
        '1107' => 'JDPay', // 京東錢包_二維
    ];

    /**
     * 支付類型對應編號
     *
     * @var array
     */
    private $payTypeMap = [
        'QQPay' => '4',
        'JDPay' => '5',
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $orderCreateDate = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['order_date'] = $orderCreateDate->format('YmdHis');

        // 二維需調整參數
        if (in_array($this->options['paymentVendorId'], ['1103', '1107'])) {
            $this->requestData['pay_type'] = $this->payTypeMap[$this->requestData['bank_code']];
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
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['sign_key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['success'] != 'Success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['billno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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

        $encodeData['sign_key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}
