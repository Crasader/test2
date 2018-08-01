<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 太和支付
 */
class TaiHePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'versionId' => '1.0', // 版本號
        'orderAmount' => '', // 金額，單位分
        'orderDate' => '', // 訂單日期，格式：YmdHis
        'currency' => 'RMB', // 幣別
        'transType' => '008', // 交易類型，固定值
        'asynNotifyUrl' => '', // 異步通知網址
        'synNotifyUrl' => '', // 同步通知網址
        'signType' => 'MD5', // 簽名方式
        'merId' => '', // 商號
        'prdOrdNo' => '', // 訂單號
        'payMode' => '', // 支付方式
        'receivableType' => 'D00', // 到帳類型，固定值
        'signData' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderAmount' => 'amount',
        'orderDate' => 'orderCreateDate',
        'asynNotifyUrl' => 'notify_url',
        'synNotifyUrl' => 'notify_url',
        'merId' => 'number',
        'prdOrdNo' => 'orderId',
        'payMode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'versionId',
        'orderAmount',
        'orderDate',
        'currency',
        'transType',
        'asynNotifyUrl',
        'synNotifyUrl',
        'signType',
        'merId',
        'prdOrdNo',
        'payMode',
        'receivableType',
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'versionId' => 1,
        'transType' => 1,
        'asynNotifyUrl' => 1,
        'synNotifyUrl' => 1,
        'merId' => 1,
        'orderStatus' => 1,
        'orderAmount' => 1,
        'prdOrdNo' => 1,
        'payId' => 1,
        'payTime' => 1,
        'signType' => 1,
        'merParam' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1092 => '00021', // 支付寶_二維
        1098 => '00028', // 支付寶_手機支付
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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['payMode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $orderCreateDate = new \DateTime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $orderCreateDate->format('YmdHis');
        $this->requestData['payMode'] = $this->bankMap[$this->requestData['payMode']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);

        // 設定支付平台需要的加密串
        $this->requestData['signData'] = $this->encode();

        // 二維支付
        if ($this->options['paymentVendorId'] == 1092) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/payment/ScanPayApply.do',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => ['Port' => '8070'],
            ];
            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['retCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['retCode'] !== '1') {
                if (isset($parseData['retMsg'])) {
                    throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
                }

                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrcode']);

            return [];
        }

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
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signData'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] === '00') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['orderStatus'] === '02') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['orderStatus'] !== '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['prdOrdNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
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
