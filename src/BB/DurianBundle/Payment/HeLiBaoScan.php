<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 合利寶掃碼
 */
class HeLiBaoScan extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'trxType' => 'AppPay', // 接口類型，固定值
        'r1_merchantNo' => '', // 商戶號
        'r2_orderNumber' => '', // 訂單號
        'r3_payType' => 'SCAN', // SCAN：掃碼
        'r4_amount' => '', // 金額，保留2位小數
        'r5_currency' => 'CNY', // 幣種，固定值
        'r6_authcode' => '0', // 二維授權碼，PayType非SWIPE填0
        'r7_appPayType' => '', // 客戶端類型
        'r8_callbackUrl' => '', // 通知回調地址
        'r9_showUrl' => '', // 可成功跳轉URL，可空
        'r10_orderIp' => '', // 用戶ip地址
        'r11_itemname' => '', // 商品描述，這邊帶入username方便業主比對
        'r12_itemattach' => '', // 商品詳情，可空
        'r13_subMerchantName' => '', // 三級商戶名稱，可空
        'r14_subMerchantId' => '', // 三級商戶編號，可空
        'r15_spUuid' => '', // 設備終端id，可空
        'r16_desc' => '', // 商戶備註，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'r1_merchantNo' => 'number',
        'r2_orderNumber' => 'orderId',
        'r4_amount' => 'amount',
        'r7_appPayType' => 'paymentVendorId',
        'r8_callbackUrl' => 'notify_url',
        'r10_orderIp' => 'ip',
        'r11_itemname' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'trxType',
        'r1_merchantNo',
        'r2_orderNumber',
        'r3_payType',
        'r4_amount',
        'r5_currency',
        'r7_appPayType',
        'r8_callbackUrl',
        'r10_orderIp',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'r1_merchantNo' => 1,
        'r2_orderNumber' => 1,
        'r3_serialNumber' => 1,
        'r4_orderStatus' => 1,
        'r5_amount' => 1,
        'r6_currency' => 1,
        'r7_timestamp' => 1,
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
        '1090' => 'WXPAY',
        '1092' => 'ALIPAY',
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'trxType' => 'AppPayQuery', // 接口類型，固定值
        'r1_merchantNo' => '', // 商戶號
        'r2_orderNumber' => '', // 訂單號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'trxType',
        'r1_merchantNo',
        'r2_orderNumber',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'r1_merchantNo' => 'number',
        'r2_orderNumber' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'trxType' => 1,
        'retCode' => 1,
        'r1_merchantNo' => 1,
        'r2_orderNumber' => 1,
        'r3_serialNumber' => 1,
        'r4_orderStatus' => 1,
        'r5_amount' => 1,
        'r6_currency' => 1,
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
        if (!array_key_exists($this->requestData['r7_appPayType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['r4_amount'] = sprintf('%.2f', $this->requestData['r4_amount']);
        $this->requestData['r7_appPayType'] = $this->bankMap[$this->requestData['r7_appPayType']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trx-service/appPay/api.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['postUrl'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['retCode'] != '0000') {
            throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['r5_qrcode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['r5_qrcode']);

        return [];
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
        $encodeStr = '#' . implode('#', $encodeData);

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['r4_orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['r2_orderNumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['r5_amount'] != $entry['amount']) {
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

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trx-service/appPay/api.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['retCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單不存在
        if ($parseData['retCode'] == '0004') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單查詢失敗
        if ($parseData['retCode'] != '0000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData) && $parseData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = '#' . implode('#', $encodeData);

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['r4_orderStatus'] == 'DOING') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['r4_orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['r2_orderNumber'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['r5_amount'] != $this->options['amount']) {
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
        $encodeStr = '#' . implode('#', $encodeData);

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
        $encodeStr = '#' . implode('#', $encodeData);

        return md5($encodeStr);
    }
}
