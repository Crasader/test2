<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新快匯寶支付
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
class NewDinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service_type'      => 'direct_pay', //業務類型，固定值
        'merchant_code'     => '', //商號
        'input_charset'     => 'UTF-8', //參數編碼
        'notify_url'        => '', //異步通知URL
        'return_url'        => '', //同步通知URL，選填
        'interface_version' => 'V3.0', //接口版本，固定值
        'sign_type'         => 'MD5', //加密方式
        'order_no'          => '', //訂單號
        'order_time'        => '', //訂單時間(Y-m-d H:i:s)
        'order_amount'      => '', //金額，精確到小數後兩位
        'product_name'      => '', //商品名稱，顯示在後台，設定username方便業主比對
        'bank_code'         => '', //銀行代碼，選填
        'redo_flag'         => '1', // 是否允許重覆訂單。1:不允許
        'sign'              => '' //加密簽名
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
        'product_name' => 'username',
        'bank_code' => 'paymentVendorId'
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
        'bank_code' => 0,
        'bank_seq_no' => 0
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
        '1' => 'ICBC', //工商銀行
        '2' => 'BCOM', //交通銀行
        '3' => 'ABC', //農業銀行
        '4' => 'CCB', //建設銀行
        '5' => 'CMB', //招商銀行
        '6' => 'CMBC', //民生銀行
        '7' => 'SDB', //深圳發展銀行
        '8' => 'SPDB', //上海浦東發展銀行
        '10' => 'CIB', //興業銀行
        '11' => 'ECITIC', //中信銀行
        '12' => 'CEBB', //光大銀行
        '13' => 'HXB', //華夏銀行
        '14' => 'GDB', //廣東發展銀行
        '15' => 'SPABANK', //平安銀行
        '16' => 'PSBC', //中國郵政儲蓄
        '17' => 'BOC', //中國銀行
        '223' => 'BEA', //東亞銀行
        '1000' => 'YDSZX', //移动神州行
        '1001' => 'LTYKT', //联通一卡通
        '1002' => 'DXGK', //电信国卡
        '1073' => 'JWYKT', //骏网一卡通
        '1074' => 'SDYKT', //盛大一卡通
        '1075' => 'ZTYKT', //征途一卡通
        '1076' => 'QBCZK', //Q币充值卡
        '1077' => 'JYYKT', //九游一卡通
        '1078' => 'WYYKT', //网易一卡通
        '1079' => 'WMYKT', //完美一卡通
        '1080' => 'SHYKT', //搜狐一卡通
        '1081' => 'ZYYKT', //纵游一卡通
        '1082' => 'TXYKT', //天下一卡通
        '1083' => 'THYKT', //天宏一卡通
        '1086' => 'TXYKTZX', //天下一卡通专项
        '1087' => 'SFYKT', //盛付通一卡通
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'service_type'      => 'single_trade_query', //業務類型，固定值
        'merchant_code'     => '', //商號
        'interface_version' => 'V3.0', //接口版本號，固定值
        'sign_type'         => 'MD5', //加密方式，固定值
        'order_no'          => '', //訂單號
        'sign'              => '' //加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_code' => 'number',
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
            '%s?pay_system=%s',
            $this->options['notify_url'],
            $this->options['merchantId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定(要送去支付平台的參數)
        $date = new \DateTime($this->requestData['order_time']);
        $this->requestData['order_time'] = $date->format("Y-m-d H:i:s");
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

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

        $this->payResultVerify();

        $encodeData = [];

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            //如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //key要放在最後面
        $encodeData['key'] = $this->privateKey;

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        //如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
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
            'uri' => '/query',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有is_success要丟例外
        if (!isset($parseData['is_success'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['is_success'] != 'T') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果沒有trade_status要丟例外
        if (!isset($parseData['trade']['trade_status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['trade']['trade_status'] == 'UNPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['trade']['trade_status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $encodeData = [];

        // 加密設定，有驗證過$parseData['trade']['trade_status']所以不用驗證是否有$parseData['trade']
        foreach ($parseData['trade'] as $key => $value) {
            if (trim($value) != '') {
                $encodeData[$key] = trim($value);
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // key要串在最後面
        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['trade']['order_amount'] != $this->options['amount']) {
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
            'path' => '/query',
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

        // 如果沒有is_success要丟例外
        if (!isset($parseData['is_success'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['is_success'] != 'T') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果沒有trade_status要丟例外
        if (!isset($parseData['trade']['trade_status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['trade']['trade_status'] == 'UNPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['trade']['trade_status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $encodeData = [];

        // 加密設定，有驗證過$parseData['trade']['trade_status']所以不用驗證是否有$parseData['trade']
        foreach ($parseData['trade'] as $key => $value) {
            if (trim($value) != '') {
                $encodeData[$key] = trim($value);
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // key要串在最後面
        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
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

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //key要串在最後面
        $encodeData['key'] = $this->privateKey;

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

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

        //key要串在最後面
        $encodeData['key'] = $this->privateKey;

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

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
        $parseData = $this->xmlToArray($content);

        //index為response內的資料才是我們需要的回傳值
        return $parseData['response'];
    }
}
