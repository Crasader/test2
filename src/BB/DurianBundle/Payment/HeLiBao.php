<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 合利寶(愛卡)
 */
class HeLiBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'P1_bizType' => 'OnlinePay', // 交易類型，固定值
        'P2_orderId' => '', // 商戶訂單號
        'P3_customerNumber' => '', // 商戶號
        'P4_orderAmount' => '', // 金額，精確到小數第二位
        'P5_bankId' => '', // 銀行編碼
        'P6_business' => 'B2C', // 業務類型，B2C:個人，B2B:企業
        'P7_timestamp' => '', // 時間戳
        'P8_goodsName' => '', // 商品名稱
        'P9_period' => '7', // 訂單有效期值
        'P10_periodUnit' => 'day', // 訂單有效期單位，day、hour、minute
        'P11_callbackUrl' => '', // 頁面回調地址
        'P12_serverCallbackUrl' => '', // 服務器回調地址
        'P13_orderIp' => '', // 用戶支付IP
        'P14_onlineCardType' => 'DEBIT', // 借貸類型 DEBIT:借記、CREDIT:貸記
        'P15_desc' => '', // 備註
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'P2_orderId' => 'orderId',
        'P3_customerNumber' => 'number',
        'P4_orderAmount' => 'amount',
        'P5_bankId' => 'paymentVendorId',
        'P7_timestamp' => 'orderCreateDate',
        'P8_goodsName' => 'username',
        'P11_callbackUrl' => 'notify_url',
        'P12_serverCallbackUrl' => 'notify_url',
        'P13_orderIp' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'P1_bizType',
        'P2_orderId',
        'P3_customerNumber',
        'P4_orderAmount',
        'P5_bankId',
        'P6_business',
        'P7_timestamp',
        'P8_goodsName',
        'P9_period',
        'P10_periodUnit',
        'P11_callbackUrl',
        'P12_serverCallbackUrl',
        'P13_orderIp',
        'P14_onlineCardType',
        'P15_desc',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'rt1_bizType' => 1,
        'rt2_retCode' => 1,
        'rt3_retMsg' => 0,
        'rt4_customerNumber' => 1,
        'rt5_orderId' => 1,
        'rt6_orderAmount' => 1,
        'rt7_bankId' => 1,
        'rt8_business' => 1,
        'rt9_timestamp' => 1,
        'rt10_completeDate' => 1,
        'rt11_orderStatus' => 1,
        'rt12_serialNumber' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMBCHINA', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PINGAN', // 平安银行
        '16' => 'POST', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'BEA', // 東亞銀行
        '226' => 'BON', // 南京銀行
        '228' => 'SRCB', // 上海農村商業銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'P1_bizType' => 'OnlineQuery', // 交易類型，固定值
        'P2_orderId' => '', // 訂單號
        'P3_customerNumber' => '', // 商戶號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'P1_bizType',
        'P2_orderId',
        'P3_customerNumber',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'P2_orderId' => 'orderId',
        'P3_customerNumber' => 'number',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'rt1_bizType' => 1,
        'rt2_retCode' => 1,
        'rt3_customerNumber' => 1,
        'rt4_orderId' => 1,
        'rt5_orderAmount' => 1,
        'rt6_bankId' => 0,
        'rt7_business' => 0,
        'rt8_createDate' => 0,
        'rt9_completeDate' => 0,
        'rt10_orderStatus' => 0,
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
        if (!array_key_exists($this->requestData['P5_bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['P4_orderAmount'] = sprintf('%.2f', $this->requestData['P4_orderAmount']);
        $this->requestData['P5_bankId'] = $this->bankMap[$this->requestData['P5_bankId']];
        $this->requestData['P7_timestamp'] = date('YmdHis', strtotime($this->options['orderCreateDate']));

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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = '&' . implode('&', $encodeData);

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['rt2_retCode'] !== '0000' || $this->options['rt11_orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['rt5_orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['rt6_orderAmount'] != $entry['amount']) {
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
        $this->setTrackingRequestData();


        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trx/online/interface.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/trx/online/interface.action',
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

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = '&' . implode('&', $encodeData);

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單不存在
        if ($parseData['rt2_retCode'] == '8102') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單查詢失敗
        if ($parseData['rt2_retCode'] !== '0000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 訂單未支付
        if ($parseData['rt10_orderStatus'] == 'INIT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單支付失敗
        if ($parseData['rt10_orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 訂單號錯誤
        if ($parseData['rt4_orderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 訂單金額錯誤
        if ($parseData['rt5_orderAmount'] != $this->options['amount']) {
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
            $encodeData[] = $this->requestData[$index];
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = '&' . implode('&', $encodeData);

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

        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[] = $this->trackingRequestData[$index];
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = '&' . implode('&', $encodeData);

        return md5($encodeStr);
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
