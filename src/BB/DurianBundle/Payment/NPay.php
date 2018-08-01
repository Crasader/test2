<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * NPay
 */
class NPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'signMethod' => 'MD5', // 簽名方式，固定值
        'signature' => '', // 簽名
        'merchantId' => '', // 商號
        'merOrderId' => '', // 訂單號
        'txnAmt' => '', // 支付金額，單位:分
        'frontUrl' => '', // 前台通知地址
        'backUrl' => '', // 後台通知地址
        'bankId' => '', // 銀行編碼
        'dcType' => '0', // 借貸類型，借記卡:0
        'subject' => '', // 商品標題，設定username方便業主比對
        'body' => '', // 商品描述，設定username方便業主比對
        'gateway' => 'bank', // 網關類型，網銀：bank
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'merOrderId' => 'orderId',
        'txnAmt' => 'amount',
        'frontUrl' => 'notify_url',
        'backUrl' => 'notify_url',
        'bankId' => 'paymentVendorId',
        'subject' => 'username',
        'body' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantId',
        'merOrderId',
        'txnAmt',
        'frontUrl',
        'backUrl',
        'bankId',
        'dcType',
        'subject',
        'body',
        'gateway',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantId' => 1,
        'merOrderId' => 1,
        'txnAmt' => 1,
        'respCode' => 1,
        'respMsg' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '01020000', // 工商銀行
        2 => '03010000', // 交通銀行
        3 => '01030000', // 農業銀行
        4 => '01050000', // 建設銀行
        5 => '03080000', // 招商銀行
        6 => '03050000', // 民生銀行
        9 => '04031000', // 北京銀行
        12 => '03030000', // 光大銀行
        14 => '03060000', // 廣東發展銀行
        16 => '01000000', // 中國郵政
        17 => '01040000', // 中國銀行
        19 => '04012900', // 上海銀行
        278 => 'kuaijie_unionpay', // 銀聯在線
        1103 => 'qqpay', // QQ_二維
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
        if (!array_key_exists($this->requestData['bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankId'] = $this->bankMap[$this->requestData['bankId']];
        $this->requestData['txnAmt'] = round($this->requestData['txnAmt'] * 100);

        // 調整銀聯在線參數
        if ($this->options['paymentVendorId'] == 278) {
            $this->requestData['gateway'] = $this->requestData['bankId'];
            unset($this->requestData['dcType']);
            unset($this->requestData['bankId']);
        }

        // 二維
        if ($this->options['paymentVendorId'] == 1103) {
            $this->requestData['gateway'] = $this->requestData['bankId'];
            unset($this->requestData['dcType']);
            unset($this->requestData['bankId']);

            $this->requestData['signature'] = $this->encode();

            // subject及body在傳送參數時需base64
            $this->requestData['subject'] = base64_encode($this->requestData['subject']);
            $this->requestData['body'] = base64_encode($this->requestData['body']);

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['success']) || !isset($parseData['msg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['success'] != '1') {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['payLink'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['payLink']);

            return [];
        }

        // 設定支付平台需要的加密串
        $this->requestData['signature'] = $this->encode();

        // subject及body在傳送參數時需base64
        $this->requestData['subject'] = base64_encode($this->requestData['subject']);
        $this->requestData['body'] = base64_encode($this->requestData['body']);

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

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有signature就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != base64_encode(md5($encodeStr, true))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] != '1001') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['txnAmt'] != round($entry['amount'] * 100)) {
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

        // 組織加密簽名，排除signature(加密簽名)、signMethod(簽名方式)，
        foreach ($this->requestData as $key => $value) {
            if ($key != 'signature' && $key != 'signMethod') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return base64_encode(md5($encodeStr, true));
    }
}