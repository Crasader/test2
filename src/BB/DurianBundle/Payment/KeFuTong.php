<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 客付通
 */
class KeFuTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'funCode' => '', // 交易類型
        'platOrderId' => '', // 訂單號
        'platMerId' => '', // 商戶號
        'tradeTime' => '', // 交易時間
        'amt' => '', // 交易總金額，單位:分
        'body' => '', // 交易說明
        'subject' => '', // 訂單標題
        'payMethod' => '', // 支付方法
        'funName' => 'prepay', // 固定prepay
        'orderTime' => '5', // 超時時間，單位為分鐘
        'notifyUrl' => '', // 異步通知地址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'funCode' => 'paymentVendorId',
        'platOrderId' => 'orderId',
        'platMerId' => 'number',
        'tradeTime' => 'orderCreateDate',
        'amt' => 'amount',
        'body' => 'orderId',
        'subject' => 'orderId',
        'payMethod' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'platOrderId' => 1,
        'platMerId' => 1,
        'outTradeNo' => 0,
        'tradeState' => 1,
        'orderAmt' => 1,
        'retCode' => 0,
        'retMsg' => 0,
        'funCode' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{retCode:"success"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => '2005', // 微信_二維
    ];

    /**
     * 支付平台支援的銀行對應支付方法
     *
     * @var array
     */
    protected $payMethodMap = [
        1090 => '0', // 微信_二維
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
        if (!array_key_exists($this->requestData['funCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['funCode'] = $this->bankMap[$this->requestData['funCode']];
        $this->requestData['payMethod'] = $this->payMethodMap[$this->requestData['payMethod']];
        $this->requestData['amt'] = round($this->requestData['amt'] * 100);
        $date = new \DateTime($this->requestData['tradeTime']);
        $this->requestData['tradeTime'] = strval($date->getTimestamp());

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/qrcode-front/qrcodeBusiness/common.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => 'reqJson=' . json_encode($this->requestData),
            'header' => ['Port' => '8380'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['retCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['retCode'] !== '0000') {
            if (isset($parseData['retMsg'])) {
                throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['codeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['codeUrl']);

        return [];
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
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeState'] != 'TRADE_SUCCESS' && $this->options['tradeState'] != 'INCOMPLETE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['platOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmt'] != round($entry['amount'] * 100)) {
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

        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && trim($this->requestData[$key]) !== '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
