<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * bosspay支付
 */
class BossPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantCode' => '', // 商號
        'KJ' => '0', // 支付方式，默認0
        'BankCode' => '', // 銀行類型
        'Amount' => '', // 金額
        'OrderId' => '',  // 訂單號
        'NotifyUrl' => '', // 異步通知網址
        'OrderDate' => '', // 請求時間
        'Sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantCode' => 'number',
        'BankCode' => 'paymentVendorId',
        'Amount' => 'amount',
        'OrderId' => 'orderId',
        'NotifyUrl' => 'notify_url',
        'OrderDate' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantCode',
        'OrderId',
        'Amount',
        'NotifyUrl',
        'OrderDate',
        'BankCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerchantCode' => 1,
        'OrderId' => 1,
        'OutTradeNo' => 1,
        'Amount' => 1,
        'OrderDate' => 1,
        'BankCode' => 1,
        'Remark' => 1,
        'Status' => 1,
        'Time' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '1090' => 'WECHAT', // 微信_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1097' => 'WECHAT_WAP', // 微信_手機支付
        '1098' => 'ALIPAY_WAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQ_WAP', // QQ_手機支付
        '1111' => 'CUP', // 銀聯_二維
        '1115' => 'WECHAT_BARCODE', // 微信支付_條碼
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
        if (!array_key_exists($this->requestData['BankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);
        $this->requestData['BankCode'] = $this->bankMap[$this->requestData['BankCode']];
        $this->requestData['OrderDate'] = strtotime($this->requestData['OrderDate']);

        // 設定支付平台需要的加密串
        $this->requestData['Sign'] = $this->encode();

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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $paymentKey . '=[' . $this->options[$paymentKey] . ']';
            }
        }

        $encodeStr .= 'TokenKey=[' . $this->privateKey . ']';

        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['OrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != $entry['amount']) {
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
        $encodeStr = '';

        foreach ($this->encodeParams as $index) {
            $encodeStr .= $index . '=[' . $this->requestData[$index] . ']';
        }

        $encodeStr .= 'TokenKey=[' . $this->privateKey . ']';

        return strtoupper(md5($encodeStr));
    }
}
