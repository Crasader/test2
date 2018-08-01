<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 微信H5支付
 */
class WeiXinH5 extends PaymentBase
{
    /**
     * 生成預付單提交參數
     *
     * @var array
     */
    protected $requestData = [
        'appid' => '', // 公眾帳號ID
        'mch_id' => '', // 商戶號
        'nonce_str' => '', // 隨機字符串(不長於32位)
        'sign' => '', // 簽名
        'body' => '', // 商品描述
        'attach' => '', // 附加數據
        'out_trade_no' => '', // 商戶訂單號
        'total_fee' => '', // 總金額，單位為分(只能為整數)
        'spbill_create_ip' => '', // 终端IP(用户端ip)
        'time_start' => '', // 訂單生成時間
        'time_expire' => '', // 交易結束時間
        'notify_url' => '', // 通知地址
        'trade_type' => 'MWEB', // 交易類型
    ];

    /**
     * 生成預付單參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'spbill_create_ip' => 'ip',
        'time_start' => 'orderCreateDate',
        'notify_url' => 'notify_url',
    ];

    /**
     * 提交預付單時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'appid',
        'mch_id',
        'nonce_str',
        'body',
        'attach',
        'out_trade_no',
        'total_fee',
        'spbill_create_ip',
        'time_start',
        'time_expire',
        'notify_url',
        'trade_type',
        'product_id',
        'scene_info',
    ];

    /**
     * 預付單解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $prepayDecodeParams = [
        'return_code' => 1,
        'return_msg' => 0,
        'appid' => 1,
        'mch_id' => 1,
        'device_info' => 0,
        'nonce_str' => 1,
        'result_code' => 1,
        'err_code' => 0,
        'err_code_des' => 0,
        'trade_type' => 1,
        'prepay_id' => 1,
        'mweb_url' => 0,
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'return_code' => 1,
        'return_msg' => 0,
        'appid' => 1,
        'mch_id' => 1,
        'device_info' => 0,
        'nonce_str' => 1,
        'sign_type' => 0,
        'result_code' => 1,
        'err_code' => 0,
        'err_code_des' => 0,
        'openid' => 1,
        'is_subscribe' => 1,
        'trade_type' => 1,
        'bank_type' => 1,
        'total_fee' => 1,
        'settlement_total_fee' => 0,
        'fee_type' => 0,
        'cash_fee' => 1,
        'cash_fee_type' => 0,
        'coupon_fee' => 0,
        'coupon_count' => 0,
        'transaction_id' => 1,
        'out_trade_no' => 1,
        'attach' => 0,
        'time_end' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '<xml><return_code>SUCCESS</return_code></xml>';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'appid' => '', // 公眾號ID
        'mch_id' => '', // 商戶號
        'out_trade_no' => '', // 商戶訂單號
        'nonce_str' => '', // 隨機字符串。不長於32位
        'sign' => '', // 簽名
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
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'appid',
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
        'return_code' => 1,
        'return_msg' => 0,
        'appid' => 1,
        'mch_id' => 1,
        'nonce_str' => 1,
        'result_code' => 1,
        'err_code' => 0,
        'err_code_des' => 0,
        'device_info' => 0,
        'openid' => 1,
        'is_subscribe' => 1,
        'trade_type' => 1,
        'trade_state' => 1,
        'bank_type' => 1,
        'total_fee' => 1,
        'fee_type' => 0,
        'cash_fee' => 1,
        'cash_fee_type' => 0,
        'coupon_fee' => 0,
        'coupon_count' => 0,
        'transaction_id' => 1,
        'out_trade_no' => 1,
        'attach' => 0,
        'time_end' => 1,
        'trade_state_desc' => 0,
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

        // 額外的參數設定
        $names = ['appid', 'wap_url', 'wap_name'];
        $extra = $this->getMerchantExtraValue($names);
        $this->requestData['appid'] = $extra['appid'];
        $this->requestData['body'] = $extra['wap_name'];
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));
        // 金額以分為單位，必須為整數
        $this->requestData['total_fee'] = round($this->options['amount'] * 100);

        // time_expire 為 time_start 的十分鐘後
        $orderDate = new \DateTime($this->requestData['time_start']);
        $this->requestData['time_start'] = $orderDate->format('YmdHis');
        $this->requestData['attach'] = strtotime($this->requestData['time_start']);
        $this->requestData['time_expire'] = $orderDate->add(new \DateInterval('PT10M'))->format('YmdHis');

        $info = [
            'h5_info' => [
                'type' => 'Wap',
                'wap_url' => $extra['wap_url'],
                'wap_name' => $this->requestData['body'],
            ],
        ];
        $this->requestData['scene_info'] = json_encode($info);

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        return $this->getPrepay();
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
        $parseData = $this->xmlToArray($this->options['content']);

        $this->options = $parseData;

        if (!isset($this->options['return_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['return_code'] !== 'SUCCESS' && isset($this->options['return_msg'])) {
            throw new PaymentConnectionException($this->options['return_msg'], 180130, $this->getEntryId());
        }

        $this->payResultVerify();

        // 正常情形為return_code和result_code皆返回SUCCESS
        if ($this->options['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有值且不為空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
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

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
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
        $extra = $this->getMerchantExtraValue(['appid']);
        $this->trackingRequestData['appid'] = $extra['appid'];
        $this->trackingRequestData['nonce_str'] = md5(uniqid(rand(), true));

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->trackingRequestData, [], 'xml');

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/orderquery',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['return_code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['return_code'] !== 'SUCCESS' && isset($parseData['return_msg'])) {
            throw new PaymentConnectionException($parseData['return_msg'], 180123, $this->getEntryId());
        }

        // 訂單不存在
        if (isset($parseData['err_code']) && $parseData['err_code'] == 'ORDERNOTEXIST') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 系統錯誤
        if (isset($parseData['err_code']) && $parseData['err_code'] == 'SYSTEMERROR') {
            throw new PaymentConnectionException(
                'System error, please try again later or contact customer service',
                180076,
                $this->getEntryId()
            );
        }

        // 正常情形為return_code和result_code皆返回SUCCESS
        if (!isset($parseData['result_code']) || $parseData['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 訂單未支付
        if (isset($parseData['trade_state']) && $parseData['trade_state'] == 'NOTPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 支付失敗
        if (isset($parseData['trade_state']) && $parseData['trade_state'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            // 如果有值且不為空值的參數才需要做加密
            if (array_key_exists($paymentKey, $parseData) && $parseData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if ($parseData['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $extra = $this->getMerchantExtraValue(['appid']);
        $this->trackingRequestData['appid'] = $extra['appid'];
        $this->trackingRequestData['nonce_str'] = md5(uniqid(rand(), true));

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->trackingRequestData, [], 'xml');

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/pay/orderquery',
            'method' => 'POST',
            'form' => $param,
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
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

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($this->options['content']);

        if (!isset($parseData['return_code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['return_code'] !== 'SUCCESS' && isset($parseData['return_msg'])) {
            throw new PaymentConnectionException($parseData['return_msg'], 180123, $this->getEntryId());
        }

        // 訂單不存在
        if (isset($parseData['err_code']) && $parseData['err_code'] == 'ORDERNOTEXIST') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 系統錯誤
        if (isset($parseData['err_code']) && $parseData['err_code'] == 'SYSTEMERROR') {
            throw new PaymentConnectionException(
                'System error, please try again later or contact customer service',
                180076,
                $this->getEntryId()
            );
        }

        // 正常情形為return_code和result_code皆返回SUCCESS
        if (!isset($parseData['result_code']) || $parseData['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 訂單未支付
        if (isset($parseData['trade_state']) && $parseData['trade_state'] == 'NOTPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 支付失敗
        if (isset($parseData['trade_state']) && $parseData['trade_state'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            // 如果有值且不為空值的參數才需要做加密
            if (array_key_exists($paymentKey, $parseData) && $parseData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if ($parseData['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
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
        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->requestData, [], 'xml');

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/unifiedorder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查返回參數
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['return_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($parseData['return_code'] !== 'SUCCESS' && isset($parseData['return_msg'])) {
            throw new PaymentConnectionException($parseData['return_msg'], 180130, $this->getEntryId());
        }

        // 驗證預付單返回參數
        foreach ($this->prepayDecodeParams as $paymentKey => $require) {
            if ($require && !isset($parseData[$paymentKey])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        // 返回參數return_code和result_code都為SUCCESS時，才會返回提交支付時需要的prepay_id
        if ($parseData['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Get prepay_id failure', 180135, $this->getEntryId());
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->prepayDecodeParams) as $paymentKey) {
            // 如果有值且不為空值的參數才需要做加密
            if (array_key_exists($paymentKey, $parseData) && $parseData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($parseData['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($parseData['mweb_url'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        return ['act_url' => $parseData['mweb_url']];
    }
}
