<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 信匯支付
 */
class ShenHui extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '3.0', // 版本號
        'method' => 'Xh.online.pay', // 接口名稱
        'partner' => '', // 商戶ID
        'banktype' => '', // 銀行類型
        'paymoney' => '', // 金額，單位元，小數點後兩位
        'ordernumber' => '', // 商戶訂單號
        'callbackurl' => '', // 異步通知地址
        'hrefbackurl' => '', // 同步通知地址，選填
        'goodsname' => '', // 商品名稱，選填
        'attach' => '', // 備註訊息，選填
        'isshow' => '1', // 是否顯示收銀台，默認1
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'paymoney' => 'amount',
        'ordernumber' => 'orderId',
        'callbackurl' => 'notify_url',
        'banktype' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'method',
        'partner',
        'banktype',
        'paymoney',
        'ordernumber',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'partner' => 1,
        'ordernumber' => 1,
        'orderstatus' => 1,
        'paymoney' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WEIXIN', // 微信二維
        '1092' => 'ALIPAY', // 支付寶二維
        '1097' => 'WEIXINWAP', // 微信_手機支付
        '1098' => 'ALIPAYWAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ二維
        '1104' => 'QQWAP', // QQ_手機支付
        '1107' => 'JD', // 京東錢包二維
        '1108' => 'JDWAP', // 京東錢包_手機支付
        '1111' => 'UNIONPAY', // 銀聯_二維
        '1115' => 'WEIXINCODE', // 微信_條碼
        '1118' => 'WEIXINCODE', // 微信條碼_手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '1.0', // 版本號
        'partner' => '', // 商戶ID
        'ordernumber' => '', // 商戶訂單號
        'sysnumber' => '', // 信匯訂單號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'partner',
        'ordernumber',
        'sysnumber',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'ordernumber' => 'orderId',
        'partner' => 'number',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'version' => 1,
        'partner' => 1,
        'ordernumber' => 1,
        'sysnumber' => 1,
        'status' => 1,
        'tradestate' => 1,
        'paymoney' => 1,
        'banktype' => 1,
        'paytime' => 1,
        'endtime' => 1,
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
        if (!array_key_exists($this->requestData['banktype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['paymoney'] = sprintf('%.2f', $this->requestData['paymoney']);
        $this->requestData['banktype'] = $this->bankMap[$this->requestData['banktype']];

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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderstatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ordernumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paymoney'] != $entry['amount']) {
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

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trade/query',
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

        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/trade/query',
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

        if (!isset($parseData['status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢失敗
        if ($parseData['status'] != '1') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['tradestate'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['tradestate'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['ordernumber'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['paymoney'] != $this->options['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

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

        $encodeData['key'] = $this->privateKey;

        $encodeStr = http_build_query($encodeData);

        return md5($encodeStr);
    }

    /**
     * 訂單查詢參數設定
     */
    private function setTrackingRequestData()
    {
        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();
    }
}
