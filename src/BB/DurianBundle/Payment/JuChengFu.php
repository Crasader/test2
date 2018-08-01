<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 聚成付
 */
class JuChengFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'payKey' => '', // 商戶支付key
        'orderPrice' => '', // 訂單交易，單位：元，保留小數點兩位
        'outTradeNo' => '', // 商戶訂單號
        'productType' => '50000103', // 產品類型，網銀:50000103
        'orderTime' => '', // 下單時間，格式YmdHis
        'productName' => '', // 支付產品名稱，帶入username
        'orderIp' => '', // 下單IP
        'bankCode' => '', // 銀行編碼(網銀參數)
        'bankAccountType' => 'PRIVATE_DEBIT_ACCOUNT', // 支付銀行卡類型(網銀參數)
        'returnUrl' => '', // 頁面通知地址
        'notifyUrl' => '', // 異步通知地址
        'remark' => '', // 備註，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'payKey' => 'number',
        'orderPrice' => 'amount',
        'outTradeNo' => 'orderId',
        'bankCode' => 'paymentVendorId',
        'orderTime' => 'orderCreateDate',
        'productName' => 'username',
        'orderIp' => 'ip',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'payKey',
        'orderPrice',
        'outTradeNo',
        'productType',
        'orderTime',
        'productName',
        'orderIp',
        'bankCode',
        'bankAccountType',
        'returnUrl',
        'notifyUrl',
        'remark',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'payKey' => 1,
        'productName' => 1,
        'outTradeNo' => 1,
        'orderPrice' => 1,
        'productType' => 1,
        'tradeStatus' => 1,
        'successTime' => 1,
        'orderTime' => 1,
        'trxNo' => 1,
        'remark' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMBCHINA', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '15' => 'PINGANBANK', // 平安銀行
        '16' => 'POST', // 郵政儲蓄銀行
        '17' => 'BOC', // 中國銀行
        '223' => 'HKBEA', // 東亞銀行
        '228' => 'SRCB', // 上海農商銀行
        '229' => 'HKB', // 漢口銀行
        '1103' => '70000203', // QQ_二維
        '1104' => '70000203', // QQ_H5手機支付
        '1107' => '80000203', // 京東_二維
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['orderPrice'] = sprintf('%.2f', $this->requestData['orderPrice']);
        $createAt = new \Datetime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $createAt->format('YmdHis');

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 二維支付、手機支付
        if (in_array($this->options['paymentVendorId'], [1103, 1104, 1107])) {
            $this->requestData['productType'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);
            unset($this->requestData['bankAccountType']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            // 二維URI
            $uri = '/cnpPay/scanPay';

            // H5手機URI
            if ($this->options['paymentVendorId'] == 1104) {
                $uri = '/cnpPay/initPay';
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => $uri,
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['resultCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resultCode'] != '0000' && !isset($parseData['errMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resultCode'] != '0000') {
                throw new PaymentConnectionException($parseData['errMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['payMessage'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            return [
                'post_url' => $parseData['payMessage'],
                'params' => [],
            ];
        }

        // 網銀，設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/b2cPay/b2cPay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['returnCode']) || !isset($parseData['returnMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['returnCode'] != '0000') {
            throw new PaymentConnectionException($parseData['returnMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' => $parseData['url'],
            'params' => [],
        ];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['paySecret'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] == 'WAITING_PAYMENT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderPrice'] != $entry['amount']) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['paySecret'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
