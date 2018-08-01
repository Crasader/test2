<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 樂盈支付
 */
class FunPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0', // 版本
        'serialID' => '', // 請求序列號(這邊帶入訂單號)
        'submitTime' => '', // 訂單提交時間
        'failureTime' => '', // 訂單失效時間(可空)
        'customerIP' => '', // 客戶下單域名及IP(可空)
        'orderDetails' => '', // 訂單明細信息
        'totalAmount' => '', // 訂單總金額(單位到分)
        'type' => '1000', // 交易類型(1000: 即时支付)
        'buyerMarked' => '', // 付款方樂盈帳戶號(可空)
        'payType' => 'BANK_B2C', // 付款方支付方式(可空)，網銀：BANK_B2C，微信：WX，支付寶：ZFB
        'orgCode' => '', // 目標資金機構代碼(可空)
        'currencyCode' => '', // 交易幣種(可空)
        'directFlag' => '1', // 是否直連(可空)
        'borrowingMarked' => '', // 資金來源借貸標識(可空)
        'couponFlag' => '', // 優惠卷標識(可空)
        'platformID' => '', // 平台商ID(可空)
        'returnUrl' => '', // 商户回調地址
        'noticeUrl' => '', // 商户通知地址
        'partnerID' => '', // 商户ID
        'remark' => 'remark', // 擴展字段
        'charset' => '1', // 编碼方式(1：UTF-8)
        'signType' => '2', // 簽名類型(2：MD5方式)
        'signMsg' => '', // 簽名字符串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'serialID' => 'orderId',
        'submitTime' => 'orderCreateDate',
        'totalAmount' => 'amount',
        'orgCode' => 'paymentVendorId',
        'returnUrl' => 'notify_url',
        'noticeUrl' => 'notify_url',
        'partnerID' => 'number',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'serialID',
        'submitTime',
        'failureTime',
        'customerIP',
        'orderDetails',
        'totalAmount',
        'type',
        'buyerMarked',
        'payType',
        'orgCode',
        'currencyCode',
        'directFlag',
        'borrowingMarked',
        'couponFlag',
        'platformID',
        'returnUrl',
        'noticeUrl',
        'partnerID',
        'remark',
        'charset',
        'signType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderID' => 1,
        'resultCode' => 1,
        'stateCode' => 1,
        'orderAmount' => 1,
        'payAmount' => 1,
        'acquiringTime' => 1,
        'completeTime' => 1,
        'orderNo' => 1,
        'partnerID' => 1,
        'remark' => 1,
        'charset' => 1,
        'signType' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '200';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'icbc', // 工商銀行
        '2' => 'comm', // 交通銀行
        '3' => 'abc', // 農業銀行
        '4' => 'ccb', // 建設銀行
        '5' => 'cmb', // 招商銀行
        '6' => 'cmbc', // 民生銀行
        '7' => 'sdb', // 深圳發展銀行
        '8' => 'spdb', // 上海浦東發展銀行
        '9' => 'bccb', // 北京銀行
        '10' => 'cib', // 興業銀行
        '11' => 'ecitic', // 中信銀行
        '12' => 'ceb', // 光大銀行
        '13' => 'hxb', // 華夏銀行
        '14' => 'gdb', // 廣東發展銀行
        '15' => 'pingan', // 平安銀行
        '16' => 'post', // 中國郵政儲蓄
        '17' => 'boc', // 中國銀行
        '222' => 'nb', // 寧波銀行
        '223' => 'bea', // 東亞銀行
        '1090' => 'wx', // 微信二維
        '1092' => 'zfb', // 支付寶二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '1.0', // 版本
        'serialID' => '', // 請求序列號(長度32)
        'mode' => '1', // 查詢模式(1:單筆)
        'type' => '1', // 查詢類型(1:支付訂單)
        'orderID' => '', // 商戶訂單號
        'beginTime' => '', // 查詢開始時間(可空)
        'endTime' => '', // 查詢結束時間(可空)
        'partnerID' => '', // 商戶ID
        'remark' => 'remark', // 擴展字段(必填欄位)
        'charset' => '1', // 編碼方式
        'signType' => '2', // 簽名類型
        'signMsg' => '', // 簽名字符串
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'orderID' => 'orderId',
        'partnerID' => 'number',
        'beginTime' => 'orderCreateDate',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'serialID',
        'mode',
        'type',
        'orderID',
        'beginTime',
        'endTime',
        'partnerID',
        'remark',
        'charset',
        'signType',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'serialID' => 1,
        'mode' => 1,
        'type' => 1,
        'resultCode' => 1,
        'queryDetailsSize' => 1,
        'queryDetails' => 1,
        'partnerID' => 1,
        'remark' => 1,
        'charset' => 1,
        'signType' => 1,
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

        // 額外的驗證項目
        if ($this->options['username'] == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['orgCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $orderDetail = [
            $this->options['orderId'],
            round($this->options['amount'] * 100),
            $this->options['username'], // 後台顯示用，方便業主查詢
            'goodsName', // 不可空，設定為goodsName
            '1',
        ];

        // 支付方式選擇微信二維：WX
        if ($this->requestData['orgCode'] == '1090') {
            $this->requestData['payType'] = 'WX';
        }

        // 支付方式選擇支付寶二維：ZFB
        if ($this->requestData['orgCode'] == '1092') {
            $this->requestData['payType'] = 'ZFB';
        }

        $submitTime = new \DateTime($this->requestData['submitTime']);
        $this->requestData['totalAmount'] = round($this->requestData['totalAmount'] * 100);
        $this->requestData['submitTime'] = $submitTime->format('YmdHis');
        $this->requestData['orderDetails'] = implode(',', $orderDetail);
        $this->requestData['orgCode'] = $this->bankMap[$this->requestData['orgCode']];

        // 設定支付平台需要的加密串
        $this->requestData['signMsg'] = $this->encode();

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

        $decodeVerifyData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $decodeVerifyData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 進行加密
        $decodeVerifyData['pkey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($decodeVerifyData));

        // 沒有signMsg就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signMsg'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['stateCode'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderID'] != $entry['id']) {
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

        // 訂單號+微秒防止同分秒的情況下被認定重複查詢(長度只能32)
        $this->trackingRequestData['serialID'] = $this->options['orderId'] . substr(microtime(), 0, 10);

        // 查詢時間區間是以提交的submitTime為準(也就是orderCreateDate)
        $date = new \DateTime($this->trackingRequestData['beginTime']);
        $this->trackingRequestData['beginTime'] = $date->format('YmdHis');
        $this->trackingRequestData['endTime'] = $date->format('YmdHis');
        $this->trackingRequestData['signMsg'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/website/queryOrderResult.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        $this->trackingResultVerify($parseData);

        // 0009為交易失敗
        if ($parseData['resultCode'] == '0009') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 進行加密
        $encodeData['pkey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        $queryDetailValue = explode(',', $parseData['queryDetails']);

        // 訂單明細的參數名稱及順序
        $queryDetailKey = [
            'orderID',
            'orderAmount',
            'payAmount',
            'acquiringTime',
            'completeTime',
            'orderNo',
            'stateCode',
        ];

        if (count($queryDetailValue) != count($queryDetailKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 組合成訂單明細
        $detail = array_combine($queryDetailKey, $queryDetailValue);

        // 沒有signMsg就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['signMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['signMsg'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 不等於2即為付款失敗
        if ($detail['stateCode'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($detail['orderAmount'] != round($this->options['amount'] * 100)) {
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
        $encodeData['pkey'] = $this->privateKey;
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

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        // 額外的加密設定
        $encodeData['pkey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param array $content
     * @return array
     */
    private function parseData($content)
    {
        $parseData = [];

        // 回傳格式為query string，因此直接用parse_str來做分解
        parse_str($content, $parseData);

        return $parseData;
    }
}
