<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易匯金二代
 */
class NewEhking extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantId' => '', // 商戶編號
        'orderAmount' => '', // 訂單金額，單位為分
        'orderCurrency' => 'CNY', // 訂單幣種
        'requestId' => '', // 訂單號
        'notifyUrl' => '', // 通知地址
        'callbackUrl' => '', // 回調地址
        'paymentModeCode' => '', // 支付方式編碼
        'payer' => ['idType' => 'IDCARD'], // 申報信息
        'productDetails' => '', // 商品信息
        'clientIp' => '', // 用戶ip
        'hmac' => '', // 參數簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'orderAmount' => 'amount',
        'requestId' => 'orderId',
        'notifyUrl' => 'notify_url',
        'callbackUrl' => 'notify_url',
        'paymentModeCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantId',
        'orderAmount',
        'orderCurrency',
        'requestId',
        'notifyUrl',
        'callbackUrl',
        'paymentModeCode',
        'productDetails',
        'clientIp',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantId' => 1, // 商戶編號
        'requestId' => 1, // 訂單號
        'serialNumber' => 1, // 交易流水號
        'totalRefundCount' => 1, // 已退款次數
        'totalRefundAmount' => 1, // 已退款金額
        'orderCurrency' => 1, // 交易幣種
        'orderAmount' => 1, // 訂單金額
        'status' => 1, // 狀態
        'completeDateTime' => 1, // 完成時間
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'BANK_CARD-B2C-ICBC-P2P', // 工商銀行
        2 => 'BANK_CARD-B2C-BOCO-P2P', // 交通銀行
        3 => 'BANK_CARD-B2C-ABC-P2P', // 農業銀行
        4 => 'BANK_CARD-B2C-CCB-P2P', // 建設銀行
        5 => 'BANK_CARD-B2C-CMBCHINA-P2P', // 招商銀行
        6 => 'BANK_CARD-B2C-CMBC-P2P', // 民生銀行
        7 => 'BANK_CARD-B2C-SDB-P2P', // 深圳發展銀行
        8 => 'BANK_CARD-B2C-SPDB-P2P', // 浦發銀行
        9 => 'BANK_CARD-B2C-BCCB-P2P', // 北京銀行
        10 => 'BANK_CARD-B2C-CIB-P2P', // 興業銀行
        11 => 'BANK_CARD-B2C-ECITIC-P2P', // 中信銀行
        12 => 'BANK_CARD-B2C-CEB-P2P', // 光大銀行
        13 => 'BANK_CARD-B2C-HXB-P2P', // 華夏銀行
        14 => 'BANK_CARD-B2C-GDB-P2P', // 廣發銀行
        15 => 'BANK_CARD-B2C-PINGANBANK-P2P', // 平安銀行
        16 => 'BANK_CARD-B2C-POST-P2P', // 郵政儲蓄銀行
        17 => 'BANK_CARD-B2C-BOC-P2P', // 中國銀行
        19 => 'BANK_CARD-B2C-SHB-P2P', // 上海銀行
        1090 => 'SCANCODE-WEIXIN_PAY-P2P', // 微信_二維
        1092 => 'SCANCODE-ALI_PAY-P2P', // 支付寶_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', // 商戶編號
        'requestId' => '', // 訂單號
        'hmac' => '', // 參數簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'requestId' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantId',
        'requestId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchantId' => 1,
        'requestId' => 1,
        'serialNumber' => 1,
        'totalRefundCount' => 1,
        'totalRefundAmount' => 1,
        'orderCurrency' => 1,
        'orderAmount' => 1,
        'status' => 1,
        'completeDateTime' => 1,
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

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 直連須傳入clientIp
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            $this->requestData['clientIp'] = $this->options['ip'];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['paymentModeCode'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $this->requestData['paymentModeCode'] = $this->bankMap[$this->requestData['paymentModeCode']];

        // 商品信息
        $this->requestData['productDetails'] = [
            [
                'name' => $this->options['username'],
                'quantity' => '1',
                'amount' => $this->requestData['orderAmount'],
                'receiver' => '',
                'description' => ''
            ]
        ];

        // 設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/onlinePay/order',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/vnd.ehking-v1.0+json'],
        ];

        $result = $this->curlRequest($curlParam);
        $retArray = json_decode($result, true);

        if (!isset($retArray['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (isset($retArray['cause']) && $retArray['status'] == 'ERROR') {
            throw new PaymentException($retArray['cause'], 180130);
        }

        // 微信、支付寶直連需顯示二維碼圖片在藍色頁面上
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            if (!isset($retArray['scanCode']) || $retArray['scanCode'] === '') {
                throw new PaymentException('Qrcode not support', 150180190);
            }

            $html = sprintf(
                '<img src="data:image/png;base64, %s"/>',
                $retArray['scanCode']
            );

            $this->setHtml($html);

            return [];
        }

        if (!isset($retArray['redirectUrl']) || $retArray['redirectUrl'] === '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return ['act_url' => $retArray['redirectUrl']];
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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['hmac'] != hash_hmac("md5", $encodeStr, $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['requestId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/onlinePay/query',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->trackingRequestData),
            'header' => ['Content-Type' => 'application/vnd.ehking-v1.0+json'],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);
        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['hmac'] != hash_hmac("md5", $encodeStr, $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['requestId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['orderAmount'] != round($this->options['amount'] * 100)) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if ($index === 'productDetails') {
                $encodeStr .= $this->requestData[$index][0]['name'];
                $encodeStr .= $this->requestData[$index][0]['quantity'];
                $encodeStr .= $this->requestData[$index][0]['amount'];

                continue;
            }

            $encodeStr .= $this->requestData[$index];
        }

        return hash_hmac("md5", $encodeStr, $this->privateKey);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        return hash_hmac("md5", $encodeStr, $this->privateKey);
    }
}
