<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * W付
 */
class WFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_code' => '', // 商號
        'service_type' => 'direct_pay', // 業務類型，direct_pay:網銀
        'notify_url' => '', // 異步通知URL
        'interface_version' => 'V3.0', // 接口版本
        'input_charset' => 'UTF-8', // 參數編碼字符集
        'sign_type' => 'RSA-S', // 加密方式
        'sign' => '', // 加密簽名
        'order_no' => '', // 訂單號
        'order_time' => '', // 訂單時間(Y-m-d H:i:s)
        'order_amount' => '', // 金額，精確到小數後兩位
        'bank_code' => '', // 銀行代碼(網銀才有)
        'redo_flag' => '1', // 是否允許重複訂單(1:不允許)
        'product_name' => '', // 商品名稱，顯示在後台，設定username方便業主比對
        'extend_param' => '', // 業務擴展參數
        'client_ip' => '', // 客戶ip
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_code' => 'number',
        'notify_url' => 'notify_url',
        'order_no' => 'orderId',
        'order_time' => 'orderCreateDate',
        'order_amount' => 'amount',
        'bank_code' => 'paymentVendorId',
        'product_name' => 'username',
        'client_ip' => 'ip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_code' => 1,
        'notify_type' => 1,
        'notify_id' => 1,
        'interface_version' => 1,
        'order_no' => 1,
        'order_time' => 1,
        'order_amount' => 1,
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
        'extra_return_param' => 0,
        'bank_seq_no' => 0,
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
        '1' =>'ICBC', // 中國工商銀行
        '2' =>'BCOM', // 交通銀行
        '3' =>'ABC', // 中國農業銀行
        '4' =>'CCB', // 中國建設銀行
        '5' =>'CMB', // 招商銀行
        '6' =>'CMBC', // 中國民生銀行
        '8' =>'SPDB', // 上海浦東發展銀行
        '9' =>'BOB', // 北京銀行
        '10' =>'CIB', // 興業銀行
        '11' =>'ECITIC', // 中信銀行
        '12' =>'CEBB', // 中國光大銀行
        '13' =>'HXB', // 華夏銀行
        '14' =>'GDB', // 廣東發展銀行
        '15' =>'SPABANK', // 深圳平安銀行
        '16' =>'PSBC', // 中國郵政
        '17' =>'BOC', // 中國銀行
        '19' =>'SHB', // 上海銀行
        '220' =>'HZB', // 杭州銀行
        '222' =>'NBB', // 寧波銀行
        '1090' => 'weixin_scan', // 微信_二維
        '1092' => 'alipay_scan', // 支付寶_二維
        '1097' => 'weixin_h5api', // 微信_手機支付
        '1098' => 'alipay_h5api', // 支付寶_手機支付
        '1103' => 'tenpay_scan', // QQ_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'service_type' => 'single_trade_query', // 業務類型，固定值
        'merchant_code' => '', // 商號
        'interface_version' => 'V3.0', // 接口版本號，固定值
        'sign_type' => 'RSA-S', // 加密方式，固定值
        'sign' => '', // 加密簽名
        'order_no' => '', // 訂單號
        'trade_no' => '', // 支付平台產生的訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_code' => 'number',
        'order_no' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchant_code' => 1,
        'order_no' => 1,
        'order_time' => 1,
        'order_amount' => 1,
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定(要送去支付平台的參數)
        $date = new \DateTime($this->requestData['order_time']);
        $this->requestData['order_time'] = $date->format('Y-m-d H:i:s');
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

        // 二維、手機支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103])) {
            // 調整額外參數設定
            $this->requestData['interface_version'] = 'V3.1';
            $this->requestData['service_type'] = $this->requestData['bank_code'];
            unset($this->requestData['bank_code']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            // 調整提交網址
            $postUrl = 'payment.https.api.' . $this->options['postUrl'];

            $url = '/gateway/api/scanpay';

            // 調整手機支付uri
            if (in_array($this->options['paymentVendorId'], [1097, 1098])) {
                $url = '/gateway/api/h5apipay';
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => $url,
                'ip' => $this->options['verify_ip'],
                'host' => $postUrl,
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = $this->parseData($result);

            if (!isset($parseData['resp_code']) || !isset($parseData['resp_desc'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resp_code'] != 'SUCCESS') {
                throw new PaymentConnectionException($parseData['resp_desc'], 180130, $this->getEntryId());
            }

            // 檢查通訊成功時，是否有回傳result_code狀態，沒有則噴錯
            if (!isset($parseData['result_code'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // result_code 狀態不為0的時候，是否有回傳錯誤訊息，沒有則噴錯
            if ($parseData['result_code'] != 0 && !isset($parseData['result_desc'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['result_code'] != 0) {
                throw new PaymentConnectionException($parseData['result_desc'], 180130, $this->getEntryId());
            }

            // 手機支付
            if (in_array($this->options['paymentVendorId'], [1097, 1098])) {
                return $this->getPhonePayData($parseData);
            }

            if (!isset($parseData['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // VIP商號支援收銀台掃碼
            if (isset($parseData['isRedirect']) && $parseData['isRedirect'] == 'Y') {
                return [
                    'post_url' => urldecode($parseData['qrcode']),
                    'params' => [],
                ];
            }

            $this->setQrcode($parseData['qrcode']);

            return [];
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 調整網銀提交網址
        $postUrl = 'https://pay.' . $this->options['postUrl'] . '/gateway?input_charset=UTF-8';

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名sign也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
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
        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/query',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->trackingRequestData)),
            'header' => []
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
        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $this->trackingRequestData['sign'] = urldecode($this->trackingRequestData['sign']);

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/query',
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
        $parseData = $this->parseData($this->options['content']);

        // 如果沒有is_success要丟例外
        if (!isset($parseData['is_success'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['is_success'] != 'T') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果沒有trade要丟例外
        if (!isset($parseData['trade'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['trade']);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $key) {
            if (array_key_exists($key, $parseData['trade'])) {
                $encodeData[$key] = $parseData['trade'][$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $sign = base64_decode($parseData['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['trade']['trade_status'] == 'UNPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['trade']['trade_status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['trade']['order_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['trade']['order_amount'] != $this->options['amount']) {
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

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        /**
         * 組織加密簽名，排除sign(加密簽名)、sign_type(簽名方式)，
         * 其他非空的參數都要納入加密
         */
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return urlencode(base64_encode($sign));
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content xml的回傳格式
     * @return array
     */
    private function parseData($content)
    {
        $parseData = $this->xmlToArray(urlencode($content));

        // index為response內的資料才是我們需要的回傳值
        return $parseData['response'];
    }

    /**
     * 取得手機支付參數
     *
     * @param array $parseData
     * @return array
     */
    private function getPhonePayData($parseData)
    {
        if (!isset($parseData['payURL'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $urlData = $this->parseUrl(urldecode($parseData['payURL']));

        // 轉字串編碼
        foreach ($urlData['params'] as $key => $param) {
            $urlData['params'][$key] = iconv('gb2312', 'utf-8', urldecode($param));
        }

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
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
