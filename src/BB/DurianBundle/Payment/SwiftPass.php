<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 威富通(自由付)
 */
class SwiftPass extends PaymentBase
{
    /**
     * 生成預付單提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => '', // 接口類型
        'version' => '2.0', // 版本號
        'sign_type' => 'MD5', // 簽名方式
        'mch_id' => '', // 商號
        'out_trade_no' => '', // 訂單號
        'body' => '', // 商品描述，不可為空
        'total_fee' => '', // 金額，單位：分
        'mch_create_ip' => '', // 訂單生成機器 IP
        'notify_url' => '', // 異步通知 URL，長度最長 255
        'nonce_str' => '', // 隨機字串，長度最長 32
        'sign' => '', // 加密簽名
    ];

    /**
     * 生成預付單提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
        'body' => 'username',
        'total_fee' => 'amount',
        'mch_create_ip' => 'ip',
        'notify_url' => 'notify_url',
        'service' => 'paymentVendorId',
    ];

    /**
     * 生成預付單時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'version',
        'sign_type',
        'mch_id',
        'out_trade_no',
        'body',
        'total_fee',
        'mch_create_ip',
        'notify_url',
        'nonce_str',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'service' => 0,
        'version' => 1,
        'charset' => 1,
        'sign_type' => 1,
        'status' => 1,
        'result_code' => 1,
        'mch_id' => 1,
        'nonce_str' => 1,
        'openid' => 0,
        'trade_type' => 1,
        'is_subscribe' => 0,
        'pay_result' => 1,
        'pay_info' => 0,
        'transaction_id' => 1,
        'out_transaction_id' => 1,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'coupon_fee' => 0,
        'fee_type' => 1,
        'bank_type' => 1,
        'time_end' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'success';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'service' => 'unified.trade.query', // 接口類型
        'version' => '2.0', // 版本號
        'sign_type' => 'MD5', // 簽名方式
        'mch_id' => '', // 商號
        'out_trade_no' => '', // 訂單號
        'nonce_str' => '', // 隨機字串，長度最長 32
        'sign' => '', // 加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'pay.weixin.native', // 微信二維
        '1092' => 'pay.alipay.native', // 支付寶二維
        '1098' => 'pay.alipay.native', // 支付寶_WAP
        '1103' => 'pay.tenpay.native', // QQ二維
        '1104' => 'pay.tenpay.wappay', // QQ_WAP
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'service',
        'version',
        'sign_type',
        'mch_id',
        'out_trade_no',
        'nonce_str',
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
        'charset' => 1,
        'sign_type' => 1,
        'status' => 1,
        'result_code' => 1,
        'mch_id' => 1,
        'nonce_str' => 1,
        'trade_state' => 1,
        'trade_type' => 1,
        'appid' => 0,
        'openid' => 0,
        'is_subscribe' => 0,
        'transaction_id' => 1,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'coupon_fee' => 0,
        'fee_type' => 1,
        'bank_type' => 1,
        'time_end' => 1,
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

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['service'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));
        $this->requestData['service'] = $this->bankMap[$this->requestData['service']];

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        // 生成預付單，並設定返回的 Qrcode
        $prepayData = $this->getPrepay();

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1098, 1104])) {
            return ['act_url' => $prepayData['code_url']];
        }

        $this->setQrcode($prepayData['code_url']);

        return [];
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

        // 先驗證平台回傳的必要參數
        if (!isset($this->options['content'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 解析xml驗證相關參數
        $this->options = $this->xmlToArray($this->options['content']);

        if (!isset($this->options['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['status'] !== '0' && isset($this->options['message'])) {
            throw new PaymentConnectionException($this->options['message'], 180130, $this->getEntryId());
        }

        if (!isset($this->options['result_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 正常情況為返回參數 status 和 result_code 都為0
        if ($this->options['status'] !== '0' || $this->options['result_code'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串，以支付平台返回為主
        foreach ($this->options as $paymentKey => $value) {
            // 除了 sign 欄位以外的非空值欄位皆須加密
            if ($paymentKey != 'sign' && $value !== '') {
                $encodeData[$paymentKey] = $value;
            }
        }
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_result'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] !== $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] !== trim(round($entry['amount'] * 100))) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $this->trackingRequestData['nonce_str'] = md5(uniqid(rand(), true));

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->trackingRequestData, [], 'xml');

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/gateway',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['status'] !== '0' && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180123, $this->getEntryId());
        }

        if (!isset($parseData['result_code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 正常情形為 status 和 result_code 皆返回 0
        if ($parseData['status'] !== '0' || $parseData['result_code'] !== '0') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 訂單未支付
        if (isset($parseData['trade_state']) && $parseData['trade_state'] == 'NOTPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 支付失敗
        if (isset($parseData['trade_state']) && $parseData['trade_state'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串，以支付平台返回為主
        foreach ($parseData as $paymentKey => $value) {
            // 除了 sign 欄位以外的非空值欄位皆須加密
            if ($paymentKey != 'sign' && $value !== '') {
                $encodeData[$paymentKey] = $value;
            }
        }
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有 sign 就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['total_fee'] !== trim(round($this->options['amount'] * 100))) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] !== '') {
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

        // 組織加密簽名
        foreach ($this->trackingEncodeParams as $index) {
            if (isset($this->trackingRequestData[$index]) && $this->trackingRequestData[$index] !== '') {
                $encodeData[$index] = $this->trackingRequestData[$index];
            }
        }
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 生成預付單
     *
     * @return array
     */
    private function getPrepay()
    {
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->requestData, [], 'xml');

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/gateway',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];
        $result = $this->curlRequest($curlParam);

        // 檢查返回參數
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($parseData['status'] !== '0' && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['result_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($parseData['result_code'] !== '0' && isset($parseData['err_msg'])) {
            throw new PaymentConnectionException($parseData['err_msg'], 180130, $this->getEntryId());
        }

        // 返回參數 status 和 result_code 都為0時，才會返回提交支付時需要的 qrcode
        if ($parseData['status'] !== '0' || $parseData['result_code'] !== '0') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        return $parseData;
    }
}
