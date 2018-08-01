<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 捷寶支付
 */
class JbPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchno' => '', // 商號
        'amount' => '', // 交易金額，保留小數點兩位，單位：元
        'traceno' => '', // 商戶訂單號
        'channel' => '2', // 連接方式，2:直連銀行
        'bankCode' => '', // 銀行代碼
        'settleType' => '2', // 結算方式，固定值
        'notifyUrl' => '', // 異步通知url
        'returnUrl' => '', // 同步通知url
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchno' => 'number',
        'amount' => 'amount',
        'traceno' => 'orderId',
        'bankCode' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'transDate' => 0,
        'transTime' => 0,
        'merchno' => 1,
        'merchName' => 0,
        'customerno' => 0,
        'amount' => 1,
        'traceno' => 1,
        'payType' => 0,
        'orderno' => 1,
        'channelOrderno' => 0,
        'channelTraceno' => 0,
        'openId' => 0,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '3002', // 工商銀行
        2 => '3020', // 交通銀行
        3 => '3005', // 農業銀行
        4 => '3003', // 建設銀行
        5 => '3001', // 招商銀行
        6 => '3006', // 民生銀行
        8 => '3004', // 浦發銀行
        9 => '3032', // 北京銀行
        10 => '3009', // 興業銀行
        11 => '3039', // 中信銀行
        12 => '3022', // 光大銀行
        14 => '3036', // 廣發銀行
        15 => '3035', // 平安銀行
        17 => '3026', // 中國銀行
        1090 => '2', // 微信_二維
        1092 => '1', // 支付寶_二維
        1103 => '8', // QQ_二維
        1104 => '8', // QQ_手機支付
        1107 => '16', // 京東錢包_二維
        1109 => '4', // 百度_二維
        1111 => '32', // 銀聯錢包_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchno' => '', // 商戶號
        'traceno' => '', // 商戶訂單號
        'signature' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchno' => 'number',
        'traceno' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'respCode' => 1,
        'message' => 1,
    ];

    /**
     * 非網銀支付銀行
     *
     * @var array
     */
    protected $nonOnlineBank = [
        1090,
        1092,
        1103,
        1104,
        1107,
        1109,
        1111,
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        // 非網銀
        if (in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            // 修改支付參數名稱
            $this->requestData['payType'] = $this->requestData['bankCode'];
            $this->requestData['settleType'] = '1';

            // 移除非網銀不需傳遞的參數
            unset($this->requestData['channel']);
            unset($this->requestData['bankCode']);
            unset($this->requestData['returnUrl']);

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            // 設定支付平台需要的加密串
            $this->requestData['signature'] = $this->encode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/passivePay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
                'charset' => 'GBK', // 需指定用GBK對數據進行編碼
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['respCode']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != '00') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['barCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // QQ_手機支付
            if ($this->options['paymentVendorId'] == 1104) {
                return ['act_url' => $parseData['barCode']];
            }

            $this->setQrcode($parseData['barCode']);

            return [];
        }

        // 設定支付平台需要的加密串
        $this->requestData['signature'] = $this->encode();

        return $this->requestData;
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) &&
                trim($this->options[$paymentKey]) !== '' &&
                $this->options[$paymentKey] !== 'null') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&' . $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['signature']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 網銀回傳狀態2是支付成功
        if (!in_array($entry['payment_vendor_id'], $this->nonOnlineBank) && $this->options['status'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 非網銀回傳狀態1是支付成功
        if (in_array($entry['payment_vendor_id'], $this->nonOnlineBank) && $this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['traceno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['signature'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $uri = '/gateway.do?m=query';

        if (in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            $uri = '/qrcodeQuery';
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->trackingRequestData)),
            'header' => [],
            'charset' => 'GBK', // 需指定用GBK對數據進行編碼
        ];

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

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['signature'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $uri = '/gateway.do?m=query';

        if (in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            $uri = '/qrcodeQuery';
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => $uri,
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
        // 取得訂單查詢結果
        $parseData = json_decode($this->options['content'], true);

        $this->trackingResultVerify($parseData);

        // 網銀查詢驗證
        if (!in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            // 查詢異常其他錯誤
            if ($parseData['respCode'] != '00') {
                throw new PaymentConnectionException($parseData['message'], 180123, $this->getEntryId());
            }

            if (!isset($parseData['status']) || $parseData['status'] != '2') {
                throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
            }

            if (!isset($parseData['amount'])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }

            // 金額錯誤
            if ($parseData['amount'] != $this->options['amount']) {
                throw new PaymentException('Order Amount error', 180058);
            }
        }

        // 非網銀查詢驗證
        if (in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            // 查詢異常其他錯誤
            if (!in_array($parseData['respCode'], ['0', '1', '2'])) {
                throw new PaymentConnectionException($parseData['message'], 180123, $this->getEntryId());
            }

            // 訂單未支付
            if ($parseData['respCode'] == '0') {
                throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
            }

            // 支付失敗
            if ($parseData['respCode'] != '1') {
                throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
            }
        }

        if (!isset($parseData['traceno'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 查詢成功則返回商戶訂單號
        if ($parseData['traceno'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }
    }

    /**
     * 處理訂單查詢支付平台返回的編碼
     *
     * 因為沒有 header 無法在 PaymentBase 中判斷
     *
     * @param array $response 訂單查詢的返回
     * @return array
     */
    public function processTrackingResponseEncoding($response)
    {
        // kue 先將回傳資料先做 base64 編碼，因此需先解開
        $body = trim(base64_decode($response['body']));

        // 將編碼轉換成UTF-8
        $body = iconv('GBK', 'UTF-8', $body);

        $response['body'] = $body;

        return $response;
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        foreach ($this->requestData as $key => $value) {
            if ($key != 'signature' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&' . $this->privateKey;

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

        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'signature' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . '&' . $this->privateKey;

        return md5($encodeStr);
    }
}
