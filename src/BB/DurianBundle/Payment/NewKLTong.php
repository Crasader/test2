<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 開連通互聯網支付
 */
class NewKLTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'inputCharset' => '1', // 編碼。1: UTF-8
        'pickupUrl' => '', // 同步通知URL，可為空。
        'receiveUrl' => '', // 異步通知URL
        'version' => 'v1.0', // 接口版本，固定值v1.0
        'language' => '1', // 語言。1: 中文
        'signType' => '0', // 加密方式。0: MD5
        'merchantId' => '', // 商號
        'payerName' => '', // 付款人姓名(這邊塞username方便業主比對)
        'payerEmail' => '', // 付款人電子郵件，可為空。
        'payerTelephone' => '', // 付款人電話，可為空。
        'orderNo' => '', // 訂單號
        'orderAmount' => '', // 訂單金額，以分為單位
        'orderCurrency' => '156', // 幣別。156: 人民幣
        'orderDatetime' => '', // 訂單時間(YmdHis)
        'orderExpireDatetime' => '', // 訂單過期時間，可為空。
        'productName' => '', // 商品名稱(這邊塞username方便業主比對)
        'productPrice' => '', // 商品價格，可為空。
        'productNum' => '', // 商品數量，可為空。
        'productId' => '', // 商品代碼，可為空。
        'productDesc' => '', // 商品描述，可為空。
        'ext1' => '', // 拓展字段，可為空。
        'ext2' => '', // 拓展字段，可為空。
        'extTL' => '', // 業務擴展字段，可為空。
        'payType' => '1', // 支付方式。1: 網銀支付
        'issuerId' => '', // 銀行代碼
        'signMsg' => '' // 簽名數據
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'receiveUrl' => 'notify_url',
        'issuerId' => 'paymentVendorId',
        'payerName' => 'username',
        'productName' => 'username',
        'orderDatetime' => 'orderCreateDate'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantId' => 1,
        'version' => 1,
        'language' => 0,
        'signType' => 1,
        'payType' => 0,
        'issuerId' => 0,
        'mchtOrderId' => 1,
        'orderNo' => 1,
        'orderDatetime' => 1,
        'orderAmount' => 1,
        'payDatetime' => 1,
        'ext1' => 0,
        'ext2' => 0,
        'payResult' => 1
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'icbc', // 工商銀行
        2 => 'comm', // 交通銀行
        3 => 'abc', // 農業銀行
        4 => 'ccb', // 建設銀行
        5 => 'cmb', // 招商銀行
        6 => 'cmbc', // 民生銀行
        8 => 'spdb', // 上海浦東發展銀行
        9 => 'bob', // 北京銀行
        10 => 'cib', // 興業銀行
        11 => 'citic', // 中信銀行
        12 => 'ceb', // 光大銀行
        13 => 'hxb', // 華夏銀行
        14 => 'cgb', // 廣東發展銀行
        15 => 'pingan', // 平安銀行
        16 => 'psbc', // 郵政儲蓄
        17 => 'boc', // 中國銀行
        19 => 'bos', // 上海銀行
        278 => '', // 銀聯在線
        1088 => '', // 銀聯在線_手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', // 商號
        'version' => 'v1.5', // 訂單查詢接口版本。固定值v1.5
        'signType' => '0', // 簽名類型(與提交時保持一致)，0: MD5
        'orderNo' => '', // 訂單號
        'orderDatetime' => '', // 訂單時間(與提交時保持一致)
        'queryDatetime' => '', // 查詢時間(北京時間，格式: YmdHis)
        'signMsg' => '' // 簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'orderNo' => 'orderId',
        'orderDatetime' => 'orderCreateDate'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchantId' => 1,
        'version' => 1,
        'language' => 0,
        'signType' => 1,
        'payType' => 0,
        'issuerId' => 0,
        'mchtOrderId' => 1,
        'orderNo' => 1,
        'orderDatetime' => 1,
        'orderAmount' => 1,
        'payDatetime' => 1,
        'ext1' => 0,
        'ext2' => 0,
        'payResult' => 1
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

        // 調整銀聯在線支付方式參數
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['payType'] = '39';
        }

        // 移除手機支付沒使用到的參數
        if ($this->options['paymentVendorId'] == 1088) {
             unset($this->requestData['payerName']);
             unset($this->requestData['payerEmail']);
             unset($this->requestData['payerTelephone']);
             unset($this->requestData['orderExpireDatetime']);
             unset($this->requestData['productPrice']);
             unset($this->requestData['productNum']);
             unset($this->requestData['productId']);
             unset($this->requestData['productDesc']);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['orderDatetime']);
        $this->requestData['orderDatetime'] = $date->format('YmdHis');
        $this->requestData['issuerId'] = $this->bankMap[$this->requestData['issuerId']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);

        // 設定支付平台需要的加密串
        $this->requestData['signMsg'] = $this->encode();

        // 調整網銀支付對外提交網址
        $postUrl = 'https://pg.' . $this->options['postUrl'] . '/gateway/index.do';

        // 調整手機支付提交提交網址
        if ($this->options['paymentVendorId'] == 1088) {
            $postUrl = 'https://mobile.' . $this->options['postUrl'] . '/mobilepay/index.do';
        }

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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signMsg'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
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

        // 調整訂單時間格式(格式為YmdHis)
        $orderDate = new \DateTime($this->trackingRequestData['orderDatetime']);
        $this->trackingRequestData['orderDatetime'] = $orderDate->format('YmdHis');

        // 設定提交時間(格式為YmdHis)
        $queryDate = new \DateTime('now');
        $this->trackingRequestData['queryDatetime'] = $queryDate->format('YmdHis');
        $this->trackingRequestData['signMsg'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/mchtoq/orderquery',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 訂單不存在
        if (isset($parseData['ERRORCODE']) && $parseData['ERRORCODE'] == '10027') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單未支付成功
        if (isset($parseData['ERRORCODE']) && $parseData['ERRORCODE'] == '10026') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $parseData) && trim($parseData[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名檔也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($parseData['signMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['signMsg'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['payResult'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['orderAmount'] != round($this->options['amount'] * 100)) {
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
        // 組織加密簽名，排除signMsg(加密簽名)，其他非空的參數都要納入加密
        foreach ($this->requestData as $key => $value) {
            if ($key != 'signMsg' && trim($value) !== '') {
                $encodeData[$key] = $value;
            }
        }

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
        // 組織加密簽名，排除signMsg(加密簽名)，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'signMsg' && trim($value) !== '') {
                $encodeData[$key] = $value;
            }
        }

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content
     * @return array
     */
    private function parseData($content)
    {
        $parseData = [];

        parse_str(urldecode($content), $parseData);

        return $parseData;
    }
}