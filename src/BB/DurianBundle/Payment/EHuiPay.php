<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * E匯支付
 */
class EHuiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Version' => '1.0', // 版本號，固定值:1.0
        'MchId' => '', // 商戶編號
        'MchOrderNo' => '', // 訂單號
        'PayType' => '', // 支付方式
        'BankCode' => '', // 銀行編碼，網銀、銀聯二維、京東二維必填
        'Amount' => '', // 訂單金額，保留小數點後兩位，單位:元
        'OrderTime' => '', // 提交訂單時間，yyyyMMddHHmmss
        'ClientIp' => '', // 請求IP
        'NotifyUrl' => '', // 通知地址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MchId' => 'number',
        'MchOrderNo' => 'orderId',
        'BankCode' => 'paymentVendorId',
        'Amount' => 'amount',
        'OrderTime' => 'orderCreateDate',
        'ClientIp' => 'ip',
        'NotifyUrl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Version',
        'MchId',
        'MchOrderNo',
        'PayType',
        'Amount',
        'OrderTime',
        'ClientIp',
        'NotifyUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'Version' => 1,
        'MchId' => 1,
        'MchOrderNo' => 1,
        'OrderId' => 1,
        'PayAmount' => 1,
        'PayResult' => 1,
        'PayMessage' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '7' => 'SDB', // 深圳發展銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SZPAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '219' => 'GZCB', // 廣州銀行
        '234' => 'BJRCB', // 北京農商
        '1088' => '', // 銀聯在線_手機支付(快捷)
        '1092' => '', // 支付寶_二維
        '1098' => '', // 支付寶_手機支付
        '1103' => '', // QQ_二維
        '1104' => '', // QQ_手機支付
        '1107' => 'JDPAY', // 京東_二維
        '1111' => 'UNIONPAY', // 銀聯_二維
    ];

    /**
     * 支付平台支援的支付方式參數
     *
     * @var array
     */
    protected $payTypeMap = [
        '1' => '50',
        '2' => '50',
        '3' => '50',
        '4' => '50',
        '5' => '50',
        '6' => '50',
        '7' => '50',
        '8' => '50',
        '9' => '50',
        '10' => '50',
        '11' => '50',
        '12' => '50',
        '13' => '50',
        '14' => '50',
        '15' => '50',
        '16' => '50',
        '17' => '50',
        '19' => '50',
        '219' => '50',
        '234' => '50',
        '1088' => '90',
        '1092' => '30',
        '1098' => '40',
        '1103' => '60',
        '1104' => '70',
        '1107' => '50',
        '1111' => '50',
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['BankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['PayType'] = $this->payTypeMap[$this->options['paymentVendorId']];
        $this->requestData['BankCode'] = $this->bankMap[$this->requestData['BankCode']];
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);
        $requestTime = new \DateTime($this->requestData['OrderTime']);
        $this->requestData['OrderTime'] = $requestTime->format('YmdHis');

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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = implode('|', $encodeData);

        $merchantExtraValues = $this->getMerchantExtraValue(['AppId']);

        $encodeStr .= '|' . $merchantExtraValues['AppId'];

        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['Sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA256)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['PayResult'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['MchOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['PayAmount'] != $entry['amount']) {
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
            $encodeData[] = $this->requestData[$index];
        }

        $encodeStr = implode('|', $encodeData);

        $merchantExtraValues = $this->getMerchantExtraValue(['AppId']);

        $encodeStr .= '|' . $merchantExtraValues['AppId'];

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA256)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
