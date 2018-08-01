<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易匯金
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
class Ehking extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p0_Cmd'   => 'Buy', // 業務類型
        'p1_MerId' => '',    // 商號
        'p2_Order' => '',    // 訂單號
        'p3_Cur'   => 'CNY', // 交易幣別
        'p4_Amt'   => '',    // 支付金額(精準到分)
        'p5_Pid'   => '',    // 商品名稱
        'p6_Pcat'  => '',    // 商品種類
        'p7_Pdesc' => '',    // 商品描述
        'p8_Url'   => '',    // 接收支付成功的地扯
        'p9_MP'    => '',    // 商戶擴展訊息
        'pa_FrpId' => '',    // 銀行代碼
        'hmac'     => ''     // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p1_MerId' => 'number',
        'p2_Order' => 'orderId',
        'p4_Amt' => 'amount',
        'p8_Url' => 'notify_url',
        'p9_MP' => 'username', // 顯示在後台，方便業主比對用
        'pa_FrpId' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p0_Cmd',
        'p1_MerId',
        'p2_Order',
        'p3_Cur',
        'p4_Amt',
        'p5_Pid',
        'p6_Pcat',
        'p7_Pdesc',
        'p8_Url',
        'p9_MP',
        'pa_FrpId'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'p1_MerId' => 1, // 商號
        'r0_Cmd' => 1, // 業務類型
        'r1_Code' => 1, // 支付結果
        'r2_TrxId' => 1, // 易匯金交易流水號
        'r3_Amt' => 1, // 支付金額
        'r4_Cur' => 1, // 交易幣別
        'r5_Pid' => 1, // 商品名稱
        'r6_Order' => 1, // 訂單號
        'r8_MP' => 1, // 商戶擴展訊息
        'r9_BType' => 1, // 交易結果返回類型
        'ro_BankOrderId' => 1, // 銀行訂單號
        'rp_PayDate' => 1 // 支付成功時間
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1   => 'ICBC-NET-B2C',       // 工商銀行
        2   => 'BOCO-NET-B2C',       // 交通銀行
        3   => 'ABC-NET-B2C',        // 中國農業銀行
        4   => 'CCB-NET-B2C',        // 建設銀行
        5   => 'CMBCHINA-NET-B2C',   // 招商銀行
        6   => 'CMBC-NET-B2C',       // 中國民生銀行
        8   => 'SPDB-NET-B2C',       // 上海浦東發展銀行
        9   => 'BCCB-NET-B2C',       // 北京銀行
        10  => 'CIB-NET-B2C',        // 興業銀行
        11  => 'ECITIC-NET-B2C',     // 中信銀行
        12  => 'CEB-NET-B2C',        // 光大銀行
        13  => 'HXB-NET-B2C',        // 華夏銀行
        15  => 'PINGANBANK-NET-B2C', // 平安銀行
        16  => 'POST-NET-B2C',       // 中國郵政
        17  => 'BOC-NET-B2C',        // 中國銀行
        19  => 'SHB-NET-B2C',        // 上海銀行
        234 => 'BJRCB-NET-B2C'       // 北京農村商業銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'p0_Cmd'   => 'QueryOrdDetail', // 業務類型
        'p1_MerId' => '',               // 商號
        'p2_Order' => '',               // 訂單號
        'hmac'     => ''                // 加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'p1_MerId' => 'number',
        'p2_Order' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'p0_Cmd',
        'p1_MerId',
        'p2_Order'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'r0_Cmd' => 1,
        'p1_MerId' => 1,
        'r1_Code' => 1,
        'r2_TrxId' => 1,
        'r3_Amt' => 1,
        'r4_Cur' => 1,
        'r6_Order' => 1,
        'r8_MP' => 1,
        'rb_PayStatus' => 1,
        'rc_RefundCount' => 1,
        'rd_RefundAmt' => 1
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pa_FrpId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['pa_FrpId'] = $this->bankMap[$this->requestData['pa_FrpId']];
        $this->requestData['p4_Amt'] = sprintf('%.2f', $this->requestData['p4_Amt']);

        // 設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // getHamc()是易匯金的加密方式
        if ($this->options['hmac'] != $this->getHamc($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['r1_Code'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['r6_Order'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['r3_Amt'] != $entry['amount']) {
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
        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/gateway/controller.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);
        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['hmac'] != $this->getHamc($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['r1_Code'] == '50') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['r1_Code'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['rb_PayStatus'] == 'INIT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['rb_PayStatus'] == 'CANCELED') {
            throw new PaymentConnectionException('Order has been cancelled', 180063, $this->getEntryId());
        }

        // 防止有其他狀態的情形發生，不等於SUCCESS即為付款失敗
        if ($parseData['rb_PayStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['r3_Amt'] != $this->options['amount']) {
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
        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/gateway/controller.action',
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

        $parseData = $this->parseData($this->options['content']);
        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['hmac'] != $this->getHamc($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['r1_Code'] == '50') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['r1_Code'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['rb_PayStatus'] == 'INIT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['rb_PayStatus'] == 'CANCELED') {
            throw new PaymentConnectionException('Order has been cancelled', 180063, $this->getEntryId());
        }

        // 防止有其他狀態的情形發生，不等於SUCCESS即為付款失敗
        if ($parseData['rb_PayStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['r3_Amt'] != $this->options['amount']) {
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
        $encodeStr = '';

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        return $this->getHamc($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        return $this->getHamc($encodeStr);
    }

    /**
     * 易匯金產生加密簽名的方式
     *
     * @param string $data
     * @return string
     */
    private function getHamc($data)
    {
        $key = $this->privateKey;

        $byteLength = 64;  // byte length for md5

        if (strlen($key) > $byteLength) {
            $key = pack("H*", md5($key));
        }

        $keyPad = str_pad($key, $byteLength, chr(0x00));
        $ipad = str_pad('', $byteLength, chr(0x36));
        $opad = str_pad('', $byteLength, chr(0x5c));

        $keyIpad = $keyPad ^ $ipad;
        $keyOpad = $keyPad ^ $opad;

        return md5($keyOpad . pack("H*", md5($keyIpad . $data)));
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

        // 將格式改成query string的格式再用parse_str來做分解
        $content = str_replace("\n", '&', urldecode($content));
        parse_str($content, $parseData);

        return $parseData;
    }
}
