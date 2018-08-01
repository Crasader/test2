<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 洋僕淘
 */
class YangPuTao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'subject' => '', // 訂單描述
        'orderSn' => '', // 訂單號
        'totalAmount' => '', // 交易金額，單位元，精確到小數點後兩位
        'notify' => '', // 回調地址
        'spbillCreateIp' => '', // 客戶端IP
        'paystyle' => '', // 支付方式
        'timestamp' => '', // 時間戳，精確到時分秒的10位數
        'mchId' => '', // 商戶號
        'channel' => '00', // 00表示全部適用
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'subject' => 'orderId',
        'orderSn' => 'orderId',
        'totalAmount' => 'amount',
        'notify' => 'notify_url',
        'spbillCreateIp' => 'ip',
        'paystyle' => 'paymentVendorId',
        'timestamp' => 'orderCreateDate',
        'mchId' => 'number',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'subject',
        'orderSn',
        'totalAmount',
        'notify',
        'spbillCreateIp',
        'paystyle',
        'timestamp',
        'mchId',
        'channel',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderSn' => 1,
        'remark' => 1,
        'totalAmount' => 1,
        'transTime' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '0000';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1103' => 'QQPAY', // QQ_二維
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
        if (!array_key_exists($this->requestData['paystyle'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['totalAmount'] = sprintf('%.2f', $this->requestData['totalAmount']);
        $this->requestData['paystyle'] = $this->bankMap[$this->requestData['paystyle']];

        $date = new \DateTime($this->requestData['timestamp']);
        $this->requestData['timestamp'] = $date->getTimestamp();

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/ScanPay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' =>  urldecode(http_build_query($this->requestData)),
            'header' => ['Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['data']['resultCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['data']['resultCode'] != 'SUCCESS') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['data']['url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['data']['url']);

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
        foreach ($this->decodeParams as $paymentKey => $require) {
            if ($require && array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderSn'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['totalAmount'] != $entry['amount']) {
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
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
