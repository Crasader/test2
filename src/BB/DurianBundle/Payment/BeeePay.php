<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 必付
 */
class BeeePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner_id' => '', // 商號
        'version' => 'V4.0.1', // 接口版本，固定值
        'service_name' => 'PTY_ONLINE_PAY', // API接口名稱，固定值
        'input_charset' => 'UTF-8', // 字符集，固定值
        'sign_type' => 'MD5', // 簽名類型，固定值
        'sign' => '', // 簽名
        'out_trade_no' => '', // 商戶訂單號
        'order_amount' => '', // 訂單金額
        'out_trade_time' => '', // 商家訂單時間
        'pay_type' => 'BANK_PAY', // 支付類型，BANK_PAY:網銀
        'bank_code' => '', // 銀行代碼
        'summary' => '', // 訂單摘要，非必填
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址，非必填
        'extend_param' => '', // 業務擴展參數，非必填
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner_id' => 'number',
        'out_trade_no' => 'orderId',
        'order_amount' => 'amount',
        'out_trade_time' => 'orderCreateDate',
        'bank_code' => 'paymentVendorId',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'partner_id',
        'version',
        'service_name',
        'input_charset',
        'sign_type',
        'out_trade_no',
        'order_amount',
        'out_trade_time',
        'pay_type',
        'bank_code',
        'summary',
        'notify_url',
        'return_url',
        'extend_param',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'partner_id' => 1,
        'input_charset' => 1,
        'notify_type' => 1,
        'order_sn' => 1,
        'order_amount' => 1,
        'order_time' => 1,
        'trade_time' => 1,
        'out_trade_no' => 1,
        'extend_param' => 0,
        'order_status' => 1,
        'sign_type' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'partner_id' => '', // 商號
        'version' => 'V4.0.1', // 接口版本，固定值
        'service_name' => 'PTY_TRADE_QUERY', // API接口名稱，固定值
        'input_charset' => 'UTF-8', // 字符集，固定值
        'sign_type' => 'MD5', // 簽名類型，固定值
        'sign' => '', // 簽名
        'out_trade_no' => '', // 商戶訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partner_id' => 'number',
        'out_trade_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'partner_id',
        'version',
        'service_name',
        'input_charset',
        'sign_type',
        'out_trade_no',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'partner_id' => 1,
        'out_trade_no' => 1,
        'order_sn' => 1,
        'order_amount' => 1,
        'order_time' => 1,
        'order_status' => 1,
        'sign_type' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'BANK_ICBC', // 中國工商銀行
        '2' => 'BANK_BOCOM', // 交通銀行
        '3' => 'BANK_ABC', // 中國農業銀行
        '4' => 'BANK_CCB', // 中國建設銀行
        '5' => 'BANK_CMB', // 招商銀行
        '6' => 'BANK_CMBC', // 中國民生銀行
        '8' => 'BANK_SPDB', // 上海浦東發展銀行
        '9' => 'BANK_BOBJ', // 北京銀行
        '10' => 'BANK_CIB', // 興業銀行
        '11' => 'BANK_CITIC', // 中信銀行
        '12' => 'BANK_CEB', // 中國光大銀行
        '13' => 'BANK_HXBC', // 華夏銀行
        '14' => 'BANK_GDB', // 廣東發展銀行
        '15' => 'BANK_PAB', // 平安銀行
        '16' => 'BANK_PSBC', // 中國郵政
        '17' => 'BANK_BOC', // 中國銀行
        '19' => 'BANK_BOS', // 上海銀行
        '217' => 'BANK_CBHB', // 渤海銀行
        '220' => 'BANK_HZCB', // 杭州銀行
        '221' => 'BANK_CZB', // 浙商銀行
        '222' => 'BANK_NBCB', // 寧波銀行
        '223' => 'BANK_BEA', // 東亞銀行
        '226' => 'BANK_BON', // 南京銀行
        '228' => 'BANK_SRCB', // 上海農村商業銀行
        '234' => 'BANK_BJRCB', // 北京農村商業銀行
        '278' => 'QPAY_UNIONPAY', // 銀聯在線
        '321' => 'BANK_TCCB', // 天津銀行
        '361' => 'BANK_ZJTLCB', // 泰隆銀行
        '1088' => 'QPAY_UNIONPAY', // 銀聯在線手機支付
        '1090' => 'WXPAY_QRCODE', // 微信_二維
        '1092' => 'ALIPAY_QRCODE', // 支付寶_二維
        '1103' => 'QQPAY_QRCODE', // QQ_二維
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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $date = new \DateTime($this->requestData['out_trade_time']);
        $this->requestData['out_trade_time'] = $date->format('Y-m-d H:i:s');

        // 快捷支付
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['pay_type'] = 'QUICK_PAY';
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            // 微信
            if ($this->options['paymentVendorId'] == 1090) {
                $this->requestData['pay_type'] = 'WXPAY';
            }

            // 支付寶
            if ($this->options['paymentVendorId'] == 1092) {
                $this->requestData['pay_type'] = 'ALIPAY';
            }

            // QQ錢包
            if ($this->options['paymentVendorId'] == 1103) {
                $this->requestData['pay_type'] = 'QQPAY';
            }

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            // 調整二維提交網址
            $verifyUrl = 'payment.https.gateway.' . $this->options['verify_url'];

            $curlParam = [
                'method' => 'POST',
                'uri' => '/gateway/payment',
                'ip' => $this->options['verify_ip'],
                'host' => $verifyUrl,
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $response = json_decode($result, true);

            if (!isset($response['respCode']) || !isset($response['respMessage'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($response['respCode'] !== 'RESPONSE_SUCCESS') {
                throw new PaymentConnectionException($response['respMessage'], 180130, $this->getEntryId());
            }

            if (!isset($response['respResult']['qrcode_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($response['respResult']['qrcode_url']);

            return [];
        }

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
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['order_status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
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

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 調整訂單查詢提交網址
        $verifyUrl = 'payment.https.query.' . $this->options['verify_url'];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/gateway/query',
            'ip' => $this->options['verify_ip'],
            'host' => $verifyUrl,
            'param' => urldecode(http_build_query($this->trackingRequestData)),
            'header' => [],
        ];

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

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 調整訂單查詢提交網址
        $verifyUrl = 'payment.https.query.' . $this->options['verify_url'];

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/gateway/query',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $verifyUrl,
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

        $response = json_decode($this->options['content'], true);

        if (!isset($response['respCode']) || !isset($response['respMessage'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($response['respCode'] !== 'RESPONSE_SUCCESS') {
            throw new PaymentConnectionException($response['respMessage'], 180123, $this->getEntryId());
        }

        if (!isset($response['respResult'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $parseData = $response['respResult'];

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $key) {
            $encodeData[$key] = $parseData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['order_status'] === '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['order_status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 查詢成功返回商戶訂單號
        if ($parseData['out_trade_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 查詢成功返回訂單金額
        if ($parseData['order_amount'] != $this->options['amount']) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            if ($this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
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

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
