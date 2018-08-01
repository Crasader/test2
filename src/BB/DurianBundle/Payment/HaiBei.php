<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 海貝支付
 */
class HaiBei extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'orderNo' => '', // 訂單號
        'merchantNo' => '', // 商號
        'orderAmount' => '', // 金額，單位分
        'notifyUrl' => '', // 異步通知網址
        'callbackUrl' => '', // 同步通知網址
        'bankName' => '', // 銀行代碼
        'currencyType' => 'CNY', // 幣別
        'productName' => '', // 商品名稱，不可空
        'productDesc' => '', // 商品描述，不可空
        'cardType' => '1', // 借記卡
        'businessType' => '01', // 業務類型，預設 01
        'remark' => '', // 備註
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderNo' => 'orderId',
        'merchantNo' => 'number',
        'orderAmount' => 'amount',
        'notifyUrl' => 'notify_url',
        'callbackUrl' => 'notify_url',
        'bankName' => 'paymentVendorId',
        'productName' => 'orderId',
        'productDesc' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'orderNo',
        'merchantNo',
        'orderAmount',
        'notifyUrl',
        'callbackUrl',
        'bankName',
        'currencyType',
        'productName',
        'productDesc',
        'cardType',
        'businessType',
        'remark',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo' => 1,
        'orderAmount' => 1,
        'orderNo' => 1,
        'wtfOrderNo' => 1,
        'orderStatus' => 1,
        'payTime' => 1,
        'productName' => 1,
        'productDesc' => 1,
        'remark' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'BOCO', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMBCHINA', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'ECITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'CGB', // 廣東發展銀行
        15 => 'PINGAN', // 平安銀行
        16 => 'POST', // 中國郵政
        17 => 'BOC', // 中國銀行
        19 => 'SHB', // 上海銀行
        217 => 'CBHB', // 渤海銀行
        309 => 'JSB', // 江蘇銀行
        1103 => '8', // QQ_二維
        1111 => '9', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bankName'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankName'] = $this->bankMap[$this->requestData['bankName']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);

        $uri = '/wappay/payapi/netpay';

        // 調整二維提交參數、uri
        if (in_array($this->options['paymentVendorId'], [1103, 1111])) {
            $this->requestData['payType'] = $this->requestData['bankName'];

            unset($this->requestData['bankName']);
            unset($this->requestData['currencyType']);
            unset($this->requestData['cardType']);
            unset($this->requestData['businessType']);

            $uri = '/wappay/payapi/order';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] != 'T') {
            if (isset($parseData['errMsg'])) {
                throw new PaymentConnectionException($parseData['errMsg'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 設定二維
        if (in_array($this->options['paymentVendorId'], [1103, 1111])) {
            $this->setQrcode($parseData['payUrl']);

            return [];
        }

        $parseUrl = parse_url($parseData['payUrl']);

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
            'query',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $params = [];
        parse_str($parseUrl['query'], $params);

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        return [
            'post_url' => $postUrl,
            'params' => $params,
        ];
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
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
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
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && $value != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
