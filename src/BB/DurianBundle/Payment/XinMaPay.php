<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新碼支付
 */
class XinMaPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'messageid' => '200001', // 二維支付，固定值
        'out_trade_no' => '', // 訂單號
        'branch_id' => '', // 商號
        'pay_type' => '', // 支付渠道
        'total_fee' => '', // 訂單金額，單位:分
        'prod_name' => '', // 訂單標題
        'prod_desc' => '', // 產品描述
        'back_notify_url' => '', // 異步通知，不能攜帶參數
        'nonce_str' => '', // 隨機字符串(不能長於32位元)
        'attach_content' => '', // 備註
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'out_trade_no' => 'orderId',
        'branch_id' => 'number',
        'pay_type' => 'paymentVendorId',
        'total_fee' => 'amount',
        'prod_name' => 'username',
        'prod_desc' => 'username',
        'back_notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'messageid',
        'out_trade_no',
        'branch_id',
        'pay_type',
        'total_fee',
        'prod_name',
        'prod_desc',
        'back_notify_url',
        'nonce_str',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'resultCode' => 1,
        'resultDesc' => 1,
        'resCode' => 1,
        'resDesc' => 1,
        'nonceStr' => 1,
        'branchId' => 1,
        'createTime' => 1,
        'orderAmt' => 1,
        'orderNo' => 1,
        'outTradeNo' => 1,
        'productDesc' => 1,
        'payType' => 0,
        'status' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"resCode":"00","resDesc":"SUCCESS"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => '10', // 微信_二維
        1092 => '20', // 支付寶_二維
        1097 => '61', // 微信_手機支付
        1098 => '62', // 支付寶_手機支付
        1103 => '50', // QQ_二維
        1104 => '63', // QQ_手機支付
        1107 => '40', // 京東_二維
        1108 => '64', // 京東_手機支付
        1111 => '70', // 銀聯_二維
        1115 => '11', // 微信支付_條碼
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'messageid' => '200003', // 訂單查詢，固定值
        'branch_id' => '', // 商戶號
        'out_trade_no' => '', // 訂單號
        'nonce_str' => '', // 隨機字符串(不能長於32位元)
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'branch_id' => 'number',
        'out_trade_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'messageid',
        'branch_id',
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
        'resultCode' => 1,
        'resultDesc' => 1,
        'resCode' => 1,
        'resDesc' => 1,
        'nonceStr' => 1,
        'branchId' => 1,
        'createTime' => 1,
        'orderAmt' => 1,
        'orderNo' => 1,
        'outTradeNo' => 1,
        'productDesc' => 1,
        'productName' => 0,
        'status' => 1,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pay_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['pay_type'] = $this->bankMap[$this->requestData['pay_type']];
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);

        // 調整手機支付提交參數與加密參數
        if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104, 1108])) {
            $this->requestData['messageid'] = '200004';
            $this->requestData['front_notify_url'] = $this->requestData['back_notify_url'];
            $this->requestData['client_ip'] = $this->options['ip'];
            $this->encodeParams[] = 'front_notify_url';
            $this->encodeParams[] = 'client_ip';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/jhpayment',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Port' => 7301],
        ];

        $result = json_decode($this->curlRequest($curlParam), true);

        $encodeData = [];

        // 組織加密串
        foreach ($result as $key => $value) {
            // 排除sign && 如果有key而且不是空值的參數才需要做加密
            if ($key != 'sign' && $value != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($result['sign'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($result['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($result['resultCode']) || !isset($result['resCode']) || !isset($result['resultDesc']) ||
            !isset($result['resDesc'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($result['resultCode'] !== '00') {
            throw new PaymentConnectionException($result['resultDesc'], 180130, $this->getEntryId());
        }

        if ($result['resCode'] !== '00') {
            throw new PaymentConnectionException($result['resDesc'], 180130, $this->getEntryId());
        }

        if (!isset($result['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 支付寶、QQ手機支付、京東手機支付
        if (in_array($this->options['paymentVendorId'], [1098, 1104, 1108])) {
            return ['act_url' => $result['payUrl']];
        }

        // 微信手機、微信條碼支付對外返回的payURL須解析
        if (in_array($this->options['paymentVendorId'], [1097, 1115])) {
            $parseUrl = parse_url($result['payUrl']);

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

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $postUrl,
                'params' => $params,
            ];
        }

        $this->setQrcode($result['payUrl']);

        return [];
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
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['resultCode'] !== '00') {
            throw new PaymentConnectionException($this->options['resultDesc'], 180130, $this->getEntryId());
        }

        if ($this->options['resCode'] !== '00') {
            throw new PaymentConnectionException($this->options['resDesc'], 180130, $this->getEntryId());
        }

        // 訂單未支付
        if ($this->options['status'] === '00') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單處理中
        if ($this->options['status'] === '01') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 訂單付款失敗
        if ($this->options['status'] !== '02') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 訂單號錯誤
        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 訂單金額錯誤
        if ($this->options['orderAmt'] != round($entry['amount'] * 100)) {
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

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/jhpayment',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->trackingRequestData),
            'header' => ['Port' => 7301],
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
        $this->verifyPrivateKey();

        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/jhpayment',
            'method' => 'POST',
            'form' => json_encode($this->trackingRequestData),
            'headers' => [
                'Host' => $this->options['verify_url'],
                'Port' => 7301,
            ],
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        $this->verifyPrivateKey();

        $parseData = json_decode($this->options['content'], true);

        if (!isset($parseData['resultCode']) || !isset($parseData['resCode']) || !isset($parseData['resultDesc']) ||
            !isset($parseData['resDesc'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['resultCode'] !== '00') {
            throw new PaymentConnectionException($parseData['resultDesc'], 180130, $this->getEntryId());
        }

        if ($parseData['resCode'] !== '00') {
            throw new PaymentConnectionException($parseData['resDesc'], 180130, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單未支付
        if ($parseData['status'] === '00') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單處理中
        if ($parseData['status'] === '01') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 訂單支付失敗
        if ($parseData['status'] !== '02') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查訂單號
        if ($parseData['outTradeNo'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($parseData['orderAmt'] != round($this->options['amount'] * 100)) {
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

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
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

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
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

        // 隨機字符串
        $this->trackingRequestData['nonce_str'] = md5(uniqid(rand(), true));

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();
    }
}
