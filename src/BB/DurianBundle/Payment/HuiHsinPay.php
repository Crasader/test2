<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯鑫支付
 */
class HuiHsinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'svcName' => 'paygate.directgatewaypay', // 服務名稱
        'merId' => '', // 商號
        'merchOrderId' => '', // 訂單號
        'amt' => '', // 金額，單位:分
        'ccy' => 'CNY', // 幣種，固定值
        'tranTime' => '', // 交易時間(Ymd H:i:s)
        'tranChannel' => '', // 交易渠道
        'merUrl' => '', // 同步通知網址
        'retUrl' => '', // 異步通知網址
        'merData' => '', // 商戶自訂數據，非必填
        'pName' => '', // 商品名稱，設定username方便業主比對
        'pCat' => '', // 商品種類，設定username方便業主比對
        'pDesc' => '', // 商品描述，設定username方便業主比對
        'md5value' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'merchOrderId' => 'orderId',
        'amt' => 'amount',
        'tranTime' => 'orderCreateDate',
        'retUrl' => 'notify_url',
        'pName' => 'username',
        'pCat' => 'username',
        'pDesc' => 'username',
        'tranChannel' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'svcName',
        'merId',
        'merchOrderId',
        'amt',
        'ccy',
        'tranTime',
        'tranChannel',
        'merUrl',
    ];

    /**
     * 二維支付時需要加密的參數
     *
     * @var array
     */
    protected $qrcodeEncodeParams = [
        'svcName',
        'merId',
        'merchOrderId',
        'amt',
        'ccy',
        'tranTime',
        'tranChannel',
        'retUrl',
        'merUserId',
        'tranType',
        'terminalType',
        'terminalId',
        'productType',
        'userIp',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchOrderId' => 1,
        'orderId' => 1,
        'tranTime' => 1,
        'amt' => 1,
        'status' => 1,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'svcName' => 'paygate.resultqry', // 服務名稱
        'merId' => '', // 商號
        'merchOrderId' => '', // 訂單號
        'tranTime' => '', // 交易時間(Ymd H:i:s)
        'md5value' => '', // MD5簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merId' => 'number',
        'merchOrderId' => 'orderId',
        'tranTime' => 'orderCreateDate',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'svcName',
        'merId',
        'merchOrderId',
        'tranTime',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchOrderId' => 1,
        'orderId' => 1,
        'amt' => 1,
        'ccy' => 1,
        'status' => 1,
        'retCode' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1000021', // 中國工商銀行
        '2' => '1000037', // 交通銀行
        '3' => '1000022', // 中國農業銀行
        '4' => '1000024', // 中國建設銀行
        '5' => '1000026', // 招商銀行
        '6' => '1000027', // 中國民生銀行
        '8' => '1000028', // 上海浦東發展銀行
        '9' => '1000036', // 北京銀行
        '10' => '1000038', // 興業銀行
        '11' => '1000030', // 中信銀行
        '12' => '1000031', // 中國光大銀行
        '14' => '1000029', // 廣東發展銀行
        '15' => '1000284', // 平安銀行
        '16' => '1000025', // 中國郵政
        '17' => '1000023', // 中國銀行
        '1090' => '4000384', // 微信支付_二維
        '1092' => '4000386', // 支付寶_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['tranChannel'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amt'] = round($this->requestData['amt'] * 100);
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['tranTime'] = $date->format('Ymd H:i:s');
        $this->requestData['tranChannel'] = $this->bankMap[$this->requestData['tranChannel']];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            return $this->getQrcodePayData();
        }

        // 設定支付平台需要的加密串
        $this->requestData['md5value'] = $this->encode();

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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        if (!isset($this->options['md5value'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['md5value'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amt'] != round($entry['amount'] * 100)) {
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

        // 額外的參數設定
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['tranTime'] = $date->format('Ymd H:i:s');

        $this->trackingRequestData['md5value'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/fm/',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->trackingRequestData)),
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

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['tranTime'] = $date->format('Ymd H:i:s');

        $this->trackingRequestData['md5value'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/fm/',
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
        $this->verifyPrivateKey();

        $parseData = [];
        parse_str($this->options['content'], $parseData);

        $this->trackingResultVerify($parseData);

        if ($parseData['retCode'] !== '000000' && isset($parseData['retMsg'])) {
            throw new PaymentConnectionException($parseData['retMsg'], 180123, $this->getEntryId());
        }

        if ($parseData['retCode'] !== '000000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        if (!isset($parseData['md5value'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 驗證簽名
        if ($parseData['md5value'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['status'] == '9') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['status'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['merchOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['amt'] != round($this->options['amount'] * 100)) {
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

        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 取得二維支付參數
     *
     * @return array
     */
    private function getQrcodePayData()
    {
        // 修改二維支付額外提交參數
        $this->requestData['svcName'] = 'paygate.thirdpay';
        $this->requestData['merUserId'] = $this->options['username'];
        $this->requestData['terminalType'] = '1';
        $this->requestData['terminalId'] = 'Mozilla/5.0';
        $this->requestData['productType'] = '3';
        $this->requestData['userIp'] = $this->options['ip'];

        // 微信_二維
        if ($this->options['paymentVendorId'] == '1090') {
            $this->requestData['tranType'] = 'WEIXIN_NATIVE';
        }

        // 支付寶_二維
        if ($this->options['paymentVendorId'] == '1092') {
            $this->requestData['tranType'] = 'ALIPAYSCAN';
        }

        // 設定支付平台需要的加密串
        $this->requestData['md5value'] = $this->qrcodeEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/fm/',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = [];
        parse_str($result, $parseData);

        if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['retCode'] != '000000') {
            throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['md5value'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付提交返回驗簽須加密的參數
        $qrcodeDecodeParams = [
            'merchOrderId',
            'orderId',
            'orderTime',
            'codeUrl',
            'prepayId',
            'retCode',
        ];

        $encodeStr = '';

        // 組織加密串
        foreach ($qrcodeDecodeParams as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        if ($parseData['md5value'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($parseData['prepayId'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['prepayId']);

        return [];
    }

    /**
     * 二維支付時的加密
     *
     * @return string
     */
    private function qrcodeEncode()
    {
        $encodeStr = '';

        foreach ($this->qrcodeEncodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}
