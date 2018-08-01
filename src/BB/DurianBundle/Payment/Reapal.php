<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 融寶支付
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class Reapal extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service'      => 'online_pay', //接口名稱。online_pay: 線上支付
        'merchant_ID'  => '', //商號
        'notify_url'   => '', //異步通知URL
        'return_url'   => '', //同步通知URL
        'sign'         => '', //加密簽名
        'sign_type'    => 'MD5', //加密方式
        'charset'      => 'utf-8', //參數編碼
        'title'        => 'title', //商品名稱，不可空，這邊先預設為title
        'body'         => 'body', //商品描述，不可空，這邊先預設為body
        'order_no'     => '', //訂單號
        'total_fee'    => '', //訂單金額，精確到小數後兩位。
        'payment_type' => '1', //支付類型。B2C時，1是代表借記卡
        'paymethod'    => 'directPay', //支付方式。directPay: 銀行直連
        'pay_cus_no'   => '', //付款客戶號，可空。(B2C為空值即可)
        'defaultbank'  => '', //銀行代碼
        'seller_email' => '', //賣家email，記錄在merchantExtra裡的seller_email
        'buyer_email'  => '' //買家email，可空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_ID' => 'number',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'order_no' => 'orderId',
        'total_fee' => 'amount',
        'defaultbank' => 'paymentVendorId'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'body' => 0,
        'is_total_fee_adjust' => 0,
        'notify_type' => 0,
        'order_no' => 1,
        'buyer_email' => 0,
        'title' => 0,
        'total_fee' => 1,
        'seller_actions' => 0,
        'quantity' => 0,
        'buyer_id' => 0,
        'trade_no' => 0,
        'notify_time' => 0,
        'gmt_payment' => 0,
        'trade_status' => 1,
        'discount' => 0,
        'is_success' => 0,
        'gmt_create' => 0,
        'price' => 0,
        'seller_id' => 0,
        'seller_email' => 0,
        'notify_id' => 1,
        'gmt_logistics_modify' => 0,
        'payment_type' => 0,
        'subject' => 0
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'   => 'ICBC', //工商銀行
        '2'   => 'BOCM', //交通銀行
        '3'   => 'ABC', //農業銀行
        '4'   => 'CCB', //建設銀行
        '5'   => 'CMB', //招商銀行
        '6'   => 'CMBC', //民生銀行
        '8'   => 'SPDB', //上海浦東發展銀行
        '9'   => 'BCCB', //北京銀行
        '10'  => 'CIB', //興業銀行
        '11'  => 'CITIC', //中信銀行
        '12'  => 'CEB', //光大銀行
        '13'  => 'HXB', //華夏銀行
        '14'  => 'CGB', //廣東發展銀行
        '15'  => 'SDB', //平安銀行
        '16'  => 'PSBC', //中國郵政儲蓄
        '17'  => 'BOC', //中國銀行
        '19'  => 'SHBANK', //上海銀行
        '278' => 'LITEPAY' //銀聯在線
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'sign'        => '', //加密簽名
        'sign_type'   => 'MD5', //加密方式
        'merchant_ID' => '', //商號
        'charset'     => 'utf-8', //編碼
        'return_type' => 'xml', //返回類型(xml, json)
        'order_no'    => '', //訂單號
        'trade_no'    => '' //融寶訂單號，可空
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_ID' => 'number',
        'order_no' => 'orderId'
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

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['defaultbank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['seller_email']);

        //額外的參數設定
        $this->requestData['total_fee'] = sprintf('%.2f', $this->requestData['total_fee']);
        $this->requestData['defaultbank'] = $this->bankMap[$this->requestData['defaultbank']];
        $this->requestData['seller_email'] = $merchantExtraValues['seller_email'];

        //設定支付平台需要的加密串
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

        //如果沒有返回通知ID要丟例外
        if (!isset($this->options['notify_id'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        /**
         * 驗證是不是融寶返回的
         * url: http://interface.reapal.com/verify/notify
         */
        $param = [
            'merchant_ID' => $entry['merchant_number'],
            'notify_id' => $this->options['notify_id']
        ];

        $curlParam = [
            'method' => 'GET',
            'uri' => '/verify/notify',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($param),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);

        if (trim(strtolower($result)) == 'invalid') {
            throw new PaymentConnectionException('Invalid pay parameters', 180129, $this->getEntryId());
        }

        //如果不是true，代表回傳的結果有異常
        if (trim(strtolower($result)) != 'true') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        $this->payResultVerify();

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            //如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        //如果沒有返回簽名擋要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] == 'WAIT_BUYER_PAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'TRADE_FINISHED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != $entry['amount']) {
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
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/query/payment',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有回傳is_success(查詢結果)要丟例外
        if (!isset($parseData['is_success'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單不存在
        if (isset($parseData['result_code']) && $parseData['result_code'] == 'TRADE_NOT_EXIST') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['is_success'] != 'T') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果沒有回傳status(交易狀態)要丟例外
        if (!isset($parseData['trade']['status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['trade']['status'] == 'wait') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['trade']['status'] != 'completed') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['trade']['amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
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
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/query/payment',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
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
        $parseData = $this->parseData($this->options['content']);

        // 如果沒有回傳is_success(查詢結果)要丟例外
        if (!isset($parseData['is_success'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單不存在
        if (isset($parseData['result_code']) && $parseData['result_code'] == 'TRADE_NOT_EXIST') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['is_success'] != 'T') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果沒有回傳status(交易狀態)要丟例外
        if (!isset($parseData['trade']['status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['trade']['status'] == 'wait') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['trade']['status'] != 'completed') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['trade']['amount'] != $this->options['amount']) {
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

        /**
         * 組織加密簽名，排除sign(加密簽名)、sign_type(簽名方式)，
         * 其他非空的參數都要納入加密
         */
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
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

        //組織加密簽名，排除sign(加密簽名)，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content xml的回傳格式
     * @return array
     */
    public function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}
