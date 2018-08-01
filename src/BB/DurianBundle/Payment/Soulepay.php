<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 嗖了支付
 */
class Soulepay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantId' => '', // 商號
        'phone' => '', // 商戶手機
        'orderNo' => '', // 訂單號
        'transAmt' => '', // 金額(精確到小數後兩位)
        'notifyUrl' => '', // 異步回調地址
        'commodityName' => '', // 商品名稱，帶入username
        'commodityDesc' => '', // 商品描述，帶入username
        'cardType' => '01', // 銀行卡類型，01:借記卡、02:貸記卡
        'bankCode' => '', // 銀行縮寫
        'returnUrl' => '', // 頁面返回地址
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'orderNo' => 'orderId',
        'transAmt' => 'amount',
        'commodityName' => 'username',
        'commodityDesc' => 'username',
        'notifyUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'returnUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantId',
        'phone',
        'orderNo',
        'transAmt',
        'commodityName',
        'notifyUrl',
        'commodityDesc',
        'cardType',
        'bankCode',
        'returnUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderNo' => 1,
        'orderId' => 1,
        'code' => 1,
        'message' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BJBANK', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXBANK', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'SPABANK', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄
        17 => 'BOC', // 中國銀行
        220 => 'HZCB', // 杭州銀行
        222 => 'NBBANK', // 寧波銀行
        226 => 'NJCB', // 南京銀行
        234 => 'BJRCB', // 北京農村商業銀行
        1090 => '3', // 微信_二維
        1092 => '2', // 支付寶_二維
        1103 => '4', // QQ_二維
        1111 => '1', // 銀聯錢包_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'phone' => '', // 商戶手機號
        'merchantId' => '', // 商號
        'orderNo' => '', // 訂單號
        'signature' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'phone',
        'merchantId',
        'orderNo',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'orderNo' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'code' => 1,
        'message' => 1,
        'orderId' => 1,
        'orderDate' => 1,
        'amount' => 1,
        'fee' => 1,
        'ordercode' => 1,
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'phone'  => '', // 商戶手機
        'merchantId' => '', // 商戶號
        'amount' => '', // 金額
        'orderNo' => '', // 訂單號
        'paymentId' => '1', // 通道ID
        'cardNo' => '', // 到賬銀行卡號
        'cardName' => '', // 到賬姓名
        'bankName' => '', // 到賬銀行
        'signature' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchantId' => 'number',
        'orderNo' => 'orderId',
        'amount' => 'amount',
        'cardNo' => 'account',
        'cardName' => 'nameReal',
        'bankName' => 'bank_info_id',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BJBANK', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXBANK', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'SPABANK', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄
        17 => 'BOC', // 中國銀行
        220 => 'HZCB', // 杭州銀行
        222 => 'NBBANK', // 寧波銀行
        226 => 'NJCB', // 南京銀行
        234 => 'BJRCB', // 北京農村商業銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'merchantId',
        'phone',
        'amount',
        'orderNo',
        'paymentId',
        'cardNo',
        'cardName',
        'bankName',
    ];

    /**
     * 出款返回驗證需要加密的參數
     *
     * @var array
     */
    protected $withdrawDecodeParams = [
        'code' => 1,
        'message' => 1,
    ];

    /**
     * 出款查詢時要提交給支付平台的參數
     *
     * @var array
     */
    protected $withdrawTrackingRequestData = [
        'phone'  => '', // 商戶手機
        'merchantId' => '', // 商戶號
        'withdrawNo' => '', // 訂單號
        'signature' => '', // 簽名
    ];

    /**
     * 出款查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawTrackingRequireMap = [
        'merchantId' => 'number',
        'withdrawNo' => 'orderId',
    ];

    /**
     * 出款查詢時需要加密的參數
     *
     * @var array
     */
    protected $withdrawTrackingEncodeParams = [
        'phone',
        'merchantId',
        'withdrawNo',
    ];

    /**
     * 出款查詢結果需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $withdrawTrackingDecodeParams = [
        'code' => 1,
        'message' => 1,
        'withdrawId' => 1,
        'withdrawDate' => 1,
        'amount' => 1,
        'fee' => 1,
        'withdrawcode' => 1,
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['phone']);

        // 額外的參數設定
        $this->requestData['transAmt'] = sprintf('%.2f', $this->requestData['transAmt']);
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['phone'] = $merchantExtraValues['phone'];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1111])) {
            $this->requestData['amount'] = $this->requestData['transAmt'];
            $this->requestData['orderNumber'] = $this->requestData['orderNo'];
            $this->requestData['returnUrl'] = $this->requestData['notifyUrl'];
            $this->requestData['paymentId'] = $this->requestData['bankCode'];

            $qrcodeEncode = [
                'merchantId',
                'phone',
                'amount',
                'notifyUrl',
                'returnUrl',
                'orderNumber',
                'paymentId',
            ];

            $this->encodeParams = $qrcodeEncode;

            $removeParams = [
                'transAmt',
                'orderNo',
                'bankCode',
                'commodityName',
                'commodityDesc',
                'cardType',
                'bankCode',
            ];

            // 移除二維不需要的參數
            foreach ($removeParams as $removeParam) {
                unset($this->requestData[$removeParam]);
            }

            // 設定支付平台需要的加密串
            $this->requestData['signature'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/api/NewPay/Receivablexm',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];
            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['code']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] != '0000') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qRcodeURL'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qRcodeURL']);

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

        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['code'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
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
            'method' => 'GET',
            'uri' => '/api/Pay/ReceivableItem',
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
            'path' => '/api/Pay/ReceivableItem?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
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
        $parseData = json_decode($this->options['content'], true);

        if (!isset($parseData['message']) || !isset($parseData['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['code'] !== '0000') {
            throw new PaymentConnectionException($parseData['message'], 180123, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        if ($parseData['ordercode'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['ordercode'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['phone']);

        // 額外的參數設定
        $this->withdrawRequestData['bankName'] = $this->withdrawBankMap[$this->withdrawRequestData['bankName']];
        $this->withdrawRequestData['amount'] = sprintf('%.2f', $this->withdrawRequestData['amount']);
        $this->withdrawRequestData['phone'] = $merchantExtraValues['phone'];

        // 設定出款需要的加密串
        $this->withdrawRequestData['signature'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'GET',
            'uri' => '/api/Pay/WithdrawPay',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        $this->withdrawResultVerify($parseData);

        // 非0000即為出款提交失敗
        if ($parseData['code'] !== '0000') {
            throw new PaymentConnectionException($parseData['message'], 180124, $this->getEntryId());
        }
    }

    /**
     * 出款訂單查詢
     */
    public function withdrawTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawTrackingVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawTrackingRequireMap as $paymentKey => $internalKey) {
            $this->withdrawTrackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['phone']);

        // 設定訂單查詢提交參數
        $this->withdrawTrackingRequestData['phone'] = $merchantExtraValues['phone'];

        $this->withdrawTrackingRequestData['signature'] = $this->withdrawTrackingEncode();

        $withdrawHost = trim($this->options['withdraw_host']);

        $curlParam = [
            'method' => 'GET',
            'uri' => '/api/Pay/WithdrawItem',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawTrackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['message'])) {
            throw new PaymentException('No withdraw tracking return parameter specified', 150180200);
        }

        // 非0000即為出款查詢失敗
        if ($parseData['code'] !== '0000') {
            throw new PaymentException($parseData['message'], 150180201);
        }

        // 驗證返回參數
        $this->withdrawTrackingResultVerify($parseData);

        // 非1即為失敗
        if ($parseData['withdrawcode'] != '1') {
            throw new PaymentException('Withdraw tracking failed', 150180198);
        }

        $totalAmount = $parseData['amount'] + $parseData['fee'];

        if ($totalAmount != $this->options['auto_withdraw_amount']) {
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

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
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

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

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

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['phone']);
        $this->trackingRequestData['phone'] = $merchantExtraValues['phone'];

        // 設定加密簽名
        $this->trackingRequestData['signature'] = $this->trackingEncode();
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->withdrawEncodeParams as $index) {
            $encodeData[$index] = $this->withdrawRequestData[$index];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 額外的加密設定
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 出款查詢時的加密
     *
     * @return string
     */
    protected function withdrawTrackingEncode()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->withdrawTrackingEncodeParams as $index) {
            $encodeData[$index] = $this->withdrawTrackingRequestData[$index];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 額外的加密設定
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
