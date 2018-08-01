<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 35支付
 */
class Pay35 extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商戶號
        'merchantCerNo' => '', // 商戶證書編號
        'sign' => '', // 加密簽名
        'signType' => 'MD5', // 簽名方式，固定值
        'outTradeNo' => '', // 商戶訂單號
        'currency' => 'CNY', // 貨幣類型，固定值
        'amount' => '', // 金額，單位：分
        'content' => '', // 交易主題，設定username方便業主比對
        'payType' => 'DEBIT_BANK_CARD_PAY', // 支付類型，DEBIT_BANK_CARD_PAY:網銀
        'outContext' => '', // 外部上下文，非必填
        'returnURL' => '', // 同步通知網址
        'callbackURL' => '', // 異步通知網址
        'settleType' => '', // 結算類型，非必填
        'settlePeriod' => '', // 結算週期，非必填
        'settleFee' => '', // 客戶結算費用，非必填
        'defaultBank' => '', // 銀行編碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'outTradeNo' => 'orderId',
        'amount' => 'amount',
        'content' => 'username',
        'returnURL' => 'notify_url',
        'callbackURL' => 'notify_url',
        'defaultBank' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantNo',
        'merchantCerNo',
        'outTradeNo',
        'currency',
        'amount',
        'content',
        'payType',
        'outContext',
        'returnURL',
        'callbackURL',
        'settleType',
        'settlePeriod',
        'settleFee',
        'defaultBank',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo' => 1,
        'tradeNo' => 1,
        'customerChannelName' => 0,
        'outTradeNo' => 1,
        'outContext' => 0,
        'payType' => 1,
        'currency' => 0,
        'amount' => 0,
        'payedAmount' => 1,
        'status' => 1,
        'settleType' => 0,
        'settlePeriod' => 0,
        'settleFee' => 0,
        'errorCode' => 0,
        'errorMsg' => 0,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantNo' => '', // 商號
        'merchantCerNo' => '', // 商戶證書編號
        'outTradeNo' => '', // 訂單號
        'sign' => '', // 加密簽名
        'signType' => 'MD5', // 簽名方式，固定值
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantNo' => 'number',
        'outTradeNo' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantNo',
        'merchantCerNo',
        'outTradeNo',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchantNo' => 1,
        'tradeNo' => 1,
        'customerChannelName' => 0,
        'outTradeNo' => 1,
        'outContext' => 0,
        'payType' => 1,
        'currency' => 1,
        'amount' => 1,
        'payedAmount' => 1,
        'status' => 1,
        'settleType' => 1,
        'settlePeriod' => 0,
        'settleFee' => 0,
        'errorCode' => 0,
        'errorMsg' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCEED';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BJBANK', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXBANK', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'SPABANK', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        220 => 'HZCB', // 杭州銀行
        222 => 'NBBANK', // 寧波銀行
        226 => 'NJCB', // 南京銀行
        234 => 'BJRCB', // 北京農村商業銀行
        1090 => 'WECHAT_QRCODE_PAY', // 微信_二維
        1092 => 'ALIPAY_QRCODE_PAY', // 支付寶_二維
        1103 => 'QQ_QRCODE_PAY', // QQ_二維
        1111 => 'UNION_QRCODE_PAY', // 銀聯錢包_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['defaultBank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['merchantCerNo']);

        // 額外的參數設定
        $this->requestData['merchantCerNo'] = $merchantExtraValues['merchantCerNo'];
        $this->requestData['defaultBank'] = $this->bankMap[$this->requestData['defaultBank']];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1111])) {
            $this->requestData['payType'] = $this->requestData['defaultBank'];

            unset($this->requestData['defaultBank']);
            $encodeParamsKey = array_search('defaultBank', $this->encodeParams);
            unset($this->encodeParams[$encodeParamsKey]);
        }

        // 設定加密簽名
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
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== 'SETTLED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payedAmount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->verifyPrivateKey();

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['merchantCerNo']);

        // 額外的參數設定
        $this->trackingRequestData['merchantCerNo'] = $merchantExtraValues['merchantCerNo'];
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/native/com.opentech.cloud.pay.trade.query/1.0.0',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $this->options['content'] = $this->curlRequest($curlParam);

        $this->paymentTrackingVerify();
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['merchantCerNo']);

        // 額外的參數設定
        $this->trackingRequestData['merchantCerNo'] = $merchantExtraValues['merchantCerNo'];
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/native/com.opentech.cloud.pay.trade.query/1.0.0',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url'],
            ],
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $parseData = json_decode($this->options['content'], true);

        if (isset($parseData['errorCode']) && isset($parseData['errorMsg'])) {
            throw new PaymentConnectionException(urldecode($parseData['errorMsg']), 180123, $this->getEntryId());
        }

        if (isset($parseData['errorCode'])) {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData) && $parseData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        // 沒有sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證簽名
        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['status'] == 'WAITING_PAY') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 不等於0即為支付失敗
        if ($parseData['status'] != 'SETTLED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 查詢成功返回商戶訂單號
        if ($parseData['outTradeNo'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 查詢成功返回訂單金額
        if ($parseData['payedAmount'] != round($this->options['amount'] * 100)) {
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
            if ($this->requestData[$index] !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return md5($encodeStr);
    }
}
