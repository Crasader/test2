<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯付寶支付
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
class Heepay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 1, // 接口版本，固定值
        'is_phone' => 0, // 微信WAP、支付寶WAP專用參數，0:掃碼、1:WAP
        'is_frame' => 0, // 微信WAP、支付寶WAP專用參數，0:個人、1:公眾號
        'pay_type' => 20, // 網銀支付代碼：20，支付寶掃碼代碼：22，微信支付代碼：30
        'pay_code' => '', // 銀行代碼
        'agent_id' => '', // 商號
        'agent_bill_id' => '', // 訂單號
        'pay_amt' => '', // 金額，保留小數點兩位，單位：元
        'notify_url' => '', // 異步通知 URL
        'return_url' => '', // 同步通知 URL
        'user_ip' => '', // 客戶 IP
        'agent_bill_time' => '', // 訂單時間(YmdHis)
        'goods_name' => '', // 商品名稱，不可為空，顯示在後台，設定username方便業主比對
        'remark' => '', // 自定義返回參數
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'pay_code' => 'paymentVendorId',
        'agent_id' => 'number',
        'agent_bill_id' => 'orderId',
        'pay_amt' => 'amount',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'user_ip' => 'ip',
        'agent_bill_time' => 'orderCreateDate',
        'goods_name' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'agent_id',
        'agent_bill_id',
        'agent_bill_time',
        'pay_type',
        'pay_amt',
        'notify_url',
        'return_url',
        'user_ip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'result' => 1,
        'agent_id' => 1,
        'jnet_bill_no' => 1,
        'agent_bill_id' => 1,
        'pay_type' => 1,
        'pay_amt' => 1,
        'remark' => 1,
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
        1 => '001', // 工商銀行
        2 => '006', // 交通銀行
        3 => '005', // 農業銀行
        4 => '003', // 建設銀行
        5 => '002', // 招商銀行
        6 => '013', // 民生銀行總行
        8 => '007', // 上海浦東發展銀行
        9 => '045', // 北京銀行
        10 => '011', // 興業銀行
        11 => '009', // 中信銀行
        12 => '010', // 光大銀行
        13 => '014', // 華夏銀行
        14 => '008', // 廣東發展銀行
        15 => '012', // 平安銀行
        16 => '020', // 中國郵政
        17 => '004', // 中國銀行
        222 => '024', // 寧波銀行
        1090 => '', // 微信支付_二維，銀行編號為空
        1092 => '', // 支付寶_二維，銀行編號為空
        1097 => '', // 微信_手機支付，銀行編號為空
        1098 => '', // 支付寶_手機支付，銀行編號為空
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '1', // 接口版本，固定值
        'agent_id' => '', // 商號
        'agent_bill_id' => '', // 訂單號
        'agent_bill_time' => '', // 訂單時間(YmdHis)
        'remark' => '', // 自定義返回參數
        'return_mode' => 1, // 查詢結果類型為字串返回
        'sign' => '', // 加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'agent_id' => 'number',
        'agent_bill_id' => 'orderId',
        'agent_bill_time' => 'orderCreateDate',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'agent_id',
        'agent_bill_id',
        'agent_bill_time',
        'return_mode',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'agent_id' => 1,
        'agent_bill_id' => 0,
        'jnet_bill_no' => 1,
        'pay_type' => 1,
        'result' => 1,
        'pay_amt' => 1,
        'pay_message' => 1,
        'remark' => 1,
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['pay_code'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定(要送去支付平台的參數)
        $date = new \DateTime($this->requestData['agent_bill_time']);
        $this->requestData['agent_bill_time'] = $date->format('YmdHis');
        $this->requestData['pay_amt'] = sprintf('%.2f', $this->requestData['pay_amt']);
        $this->requestData['remark'] = sprintf(
            '%s_%s',
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 須將 ip 中的 . 改為 _
        $this->requestData['user_ip'] = str_replace('.', '_', $this->requestData['user_ip']);

        // 網銀支付代碼：20，支付寶代碼：22，微信代碼：30
        if (in_array($this->requestData['pay_code'], [1092, 1098])) {
            $this->requestData['pay_type'] = 22;
        }

        if (in_array($this->requestData['pay_code'], [1090, 1097])) {
            $this->requestData['pay_type'] = 30;
        }

        // H5額外參數設定
        if (in_array($this->requestData['pay_code'], [1097, 1098])) {
            $this->requestData['is_phone'] = '1';
        }

        //　微信H5額外參數設定
        if ($this->requestData['pay_code'] == 1097) {
            $parseNotifyUrl = parse_url($this->requestData['notify_url']);
            $hostUrl = $parseNotifyUrl['scheme'] . '://' . $parseNotifyUrl['host'];

            $metaOption = [
                's' => 'WAP',
                'n' => $this->options['username'],
                'id' => $hostUrl,
            ];

            $metaOptionEncode = urlencode(iconv('utf-8', 'gb2312', base64_encode(json_encode($metaOption))));
            $this->requestData['meta_option'] = $metaOptionEncode;
        }

        $this->requestData['pay_code'] = $this->bankMap[$this->requestData['pay_code']];

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
            // 如果有key的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] != 1) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['agent_bill_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['pay_amt'] != $entry['amount']) {
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

        $date = new \DateTime($this->trackingRequestData['agent_bill_time']);
        $this->trackingRequestData['agent_bill_time'] = $date->format('YmdHis');
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Payment/Query.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->trackingRequestData)),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);
        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            // 如果有 key 的參數才需要做加密
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $paymentKey . '=' . $parseData[$paymentKey] . '|';
            }
        }
        $encodeStr .= 'key=' . $this->privateKey;

        // 沒有 sign 就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['pay_amt'] != $this->options['amount']) {
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

        // 額外的加密設定
        $encodeData['key'] = $this->privateKey;
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

        // 組織加密簽名
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content
     * @return array
     */
    public function parseData($content)
    {
        /**
         * 回傳範例：
         * agent_id=1001|agent_bill_id=2005032001234|jnet_bill_no=B070606017329737|pay_type=10|resul
         * t=1|pay_amt=12.01|pay_message=test|remark=test_remark|sign=6f8fb4aeeafac5820979a86f0d2d1300
         */
        $regularResult = [];
        preg_match_all('/([^|]*)=([^|]*)/', $content, $regularResult);
        $parseData = array_combine($regularResult[1], $regularResult[2]);

        return $parseData;
    }
}
