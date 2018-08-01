<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 艾米森
 */
class Amxmy extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'src_code' => '', // 渠道代碼(商戶唯一標示)
        'sign' => '', // 簽名
        'out_trade_no' => '', // 訂單號
        'total_fee' => '', // 金額，單位:分
        'time_start' => '', // 交易時間
        'goods_name' => '', // 產品名稱，設定username方便業主比對
        'trade_type' => '', // 交易類型，網銀:80103
        'finish_url' => '', // 支付完成跳轉URL，網銀必填
        'out_mchid' => '', // 接入方商號，非必填
        'mchid' => '', // 商戶號
        'time_expire' => '', // 訂單有效期，默認有效期為半小時，非必填
        'fee_type' => 'CNY', // 貨幣類型，固定值
        'goods_detail' => '', // 商品詳細，非必填
        'openid' => '', // 微信的openid，僅用於公眾號之付，非必填
        'auth_code' => '', // 授權碼，僅用於刷卡支付，非必填
        'limit_pay' => '', // 限制信用卡支付，非必填
        'extend' => '', // 擴展域，網銀用，json格式，包括bankName、cardType
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'time_start' => 'orderCreateDate',
        'goods_name' => 'username',
        'trade_type' => 'paymentVendorId',
        'finish_url' => 'notify_url',
        'mchid' => 'number',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'src_code',
        'out_trade_no',
        'total_fee',
        'time_start',
        'goods_name',
        'trade_type',
        'finish_url',
        'out_mchid',
        'mchid',
        'time_expire',
        'fee_type',
        'goods_detail',
        'openid',
        'auth_code',
        'limit_pay',
        'extend',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'src_code' => 1,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'time_start' => 1,
        'pay_time' => 1,
        'total_fee' => 1,
        'trade_type' => 1,
        'fee_type' => 1,
        'goods_name' => 1,
        'goods_detail' => 1,
        'order_status' => 1,
        'order_type' => 1,
        'cancel' => 1,
        'out_mchid' => 1,
        'mchid' => 1,
        'orig_trade_no' => 1,
        'time_expire' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * Payment 專案表單提交的 method
     *
     * @var string
     */
    protected $payMethod = 'GET';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'src_code' => '', // 渠道代碼(商戶唯一標示)
        'sign' => '', // 簽名
        'trade_no' => '', // 訂單號，非必填
        'out_trade_no' => '', // 商戶訂單號
        'start_time' => '', // 查詢交易開始時間，非必填
        'end_time' => '', // 查詢交易結束時間，非必填
        'page' => '', // 頁數，非必填
        'page_size' => '', // 每頁顯示的數據，非必填
        'trade_type' => '', // 交易類型，非必填
        'out_mchid' => '', // 接入方商戶號，非必填
        'mchid' => '', // 商戶號，非必填
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'out_trade_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'src_code',
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
        'src_code' => 1,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'time_start' => 1,
        'pay_time' => 1,
        'trade_type' => 1,
        'goods_name' => 1,
        'goods_detail' => 1,
        'fee_type' => 1,
        'order_status' => 1,
        'order_type' => 1,
        'cancel' => 1,
        'out_mchid' => 1,
        'mchid' => 1,
        'orig_trade_no' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '工商银行', // 中國工商銀行
        '2' => '交通银行', // 交通銀行
        '3' => '农业银行', // 中國農業銀行
        '4' => '建设银行', // 中國建設銀行
        '5' => '招商银行', // 招商銀行
        '6' => '民生银行', // 中國民生銀行
        '8' => '浦发银行', // 上海浦東發展銀行
        '9' => '北京银行', // 北京銀行
        '10' => '兴业银行', // 興業銀行
        '11' => '中信银行', // 中信銀行
        '14' => '广发银行', // 廣東發展銀行
        '15' => '平安银行', // 平安銀行
        '16' => '邮政储蓄银行', // 中國郵政
        '17' => '中国银行', // 中國銀行
        '19' => '上海银行', // 上海银行
        '278' => '银联通道', // 銀聯在線
        '1090' => '50104', // 微信支付_二維
        '1092' => '60104', // 支付寶_二維
        '1097' => '50107', // 微信_手機支付
        '1098' => '60107', // 支付寶_手機支付
        '1103' => '40104', // QQ_二維
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
        if (!array_key_exists($this->requestData['trade_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['src_code']);

        // 額外的參數設定
        $this->requestData['src_code'] = $merchantExtraValues['src_code'];
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['time_start'] = $date->format('YmdHis');

        // 非網銀支付參數設定
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103])) {
            $this->requestData['trade_type'] = $this->bankMap[$this->requestData['trade_type']];
        }

        // 網銀支付參數設定
        if (!in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103])) {
            $extendParam = [
                'bankName' => $this->bankMap[$this->requestData['trade_type']],
                'cardType' => '借记卡',
            ];

            $this->requestData['trade_type'] = '80103';
            $this->requestData['extend'] = json_encode($extendParam);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trade/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['respcd']) || !isset($parseData['respmsg']) || !isset($parseData['data'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['respcd'] != '0000') {
            throw new PaymentConnectionException($parseData['respmsg'], 180130, $this->getEntryId());
        }

        $data = $parseData['data'];

        // 沒有sign或是pay_params丟例外
        if (!isset($data['sign']) || !isset($data['pay_params'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $encodeData = [];

        foreach ($data as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&key=' . $this->privateKey;

        // 驗證簽名
        if (strtoupper($data['sign']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            $this->setQrcode($data['pay_params']);

            return [];
        }

        $parseUrl = parse_url($data['pay_params']);

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $params = [];

        if (isset($parseUrl['query'])) {
            parse_str($parseUrl['query'], $params);
        }

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
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&key=' . $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['order_status'] != '3') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['src_code']);

        // 額外的參數設定
        $this->trackingRequestData['src_code'] = $merchantExtraValues['src_code'];

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

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
        $merchantExtraValues = $this->getMerchantExtraValue(['src_code']);

        // 額外的參數設定
        $this->trackingRequestData['src_code'] = $merchantExtraValues['src_code'];

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

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

        if (!isset($parseData['respcd']) || !isset($parseData['respmsg']) || !isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['respcd'] !== '0000') {
            throw new PaymentConnectionException($parseData['respmsg'], 180123, $this->getEntryId());
        }

        if (!isset($parseData['data'][0])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $data = $parseData['data'][0];

        // 沒有sign丟例外
        if (!isset($data['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($data);

        $encodeData = [];

        foreach ($data as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&key=' . $this->privateKey;

        // 驗證簽名
        if (strtoupper($data['sign']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($data['order_status'] == '1') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($data['order_status'] == '2') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($data['order_status'] != '3') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($data['out_trade_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($data['total_fee'] != round($this->options['amount'] * 100)) {
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
            if (trim($this->requestData[$index]) != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&key=' . $this->privateKey;

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

        $encodeStr = urldecode(http_build_query($encodeData)) . '&key=' . $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}
