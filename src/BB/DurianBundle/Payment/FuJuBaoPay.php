<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 付聚寶
 */
class FuJuBaoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantId' => '', // 商戶號
        'notifyUrl' => '', // 異步通知網址
        'returnUrl' => '', // 同步通知網址
        'sign' => '', // 簽名
        'outOrderId' => '', // 商戶訂單號
        'subject' => '', // 訂單名稱，放入orderid
        'body' => '', // 訂單描述，不可為空
        'transAmt' => '', // 訂單金額
        'defaultBank' => '', // 銀行代碼
        'channel' => 'B2C', // 默認渠道，固定值:B2C
        'cardAttr' => '1', // 卡類型，1:借記卡
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'outOrderId' => 'orderId',
        'subject' => 'orderId',
        'body' => 'orderId',
        'transAmt' => 'amount',
        'defaultBank' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantId',
        'notifyUrl',
        'returnUrl',
        'outOrderId',
        'subject',
        'body',
        'transAmt',
        'defaultBank',
        'channel',
        'cardAttr',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'outOrderId' => 1,
        'subject' => 0,
        'body' => 0,
        'transAmt' => 1,
        'merchantId' => 1,
        'localOrderId' => 1,
        'transTime' => 1,
        'respType' => 1,
        'respCode' => 1,
        'respMsg' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '01020000', // 中國工商銀行
        '2' => '03010000', //交通銀行
        '3' => '01030000', // 中國農業銀行
        '4' => '01050000', // 中國建設銀行
        '5' => '03080000', // 招商銀行
        '6' => '03050000', //中國民生銀行
        '8' => '03100000', // 浦發銀行
        '10' => '03090000', // 興業銀行
        '11' => '03020000', // 中信銀行
        '12' => '03030000', // 中國光大銀行
        '13' => '03040000', // 華夏銀行
        '14' => '03060000', // 廣東發展銀行
        '15' => '03070000', // 平安銀行
        '16' => '01000000', // 中國郵政
        '17' => '01040000', // 中國銀行
        '278' => '', // 銀聯在線
        '1088' => '', // 銀聯在線_手機支付
        '1103' => '30000003', // QQ_二維
        '1104' => '30000004', // QQ_手機支付
        '1111' => '80000008', // 銀聯_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', // 商戶號
        'sign' => '', // 簽名
        'queryId' => '', // 商戶查詢號
        'outOrderId' => '', // 商戶訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'queryId' => 'orderId',
        'outOrderId' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantId',
        'queryId',
        'outOrderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'queryId' => 1,
        'respType' => 1,
        'respCode' => 1,
        'respMsg' => 1,
        'merchantId' => 1,
        'localOrderId' => 1,
        'outOrderId' => 1,
        'oriRespType' => 1,
        'oriRespCode' => 1,
        'oriRespMsg' => 1,
        'transAmt' => 1,
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'merchantId' => '', // 商戶ID
        'channelId' => '0', // 通道ID，傳0自動選擇通道
        'notifyUrl' => '', // 通知URL
        'sign' => '', // 簽名
        'outOrderId' => '', // 訂單號
        'subject' => '', // 訂單標題，必填
        'remark' => '', // 訂單描述，必填
        'payAmount' => '', // 金額，單位:元
        'cardType' => '2', // 卡類型，1:對公 2:對私
        'cardHolder' => '', // 戶名
        'bankCardNo' => '', // 卡號
        'bankCode' => '', // 銀行編碼
        'bankBranchName' => '', // 支行名稱
        'bankProvince' => '', // 開戶省
        'bankCity' => '', // 開戶市
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchantId' => 'number',
        'notifyUrl' => 'shop_url',
        'outOrderId' => 'orderId',
        'subject' => 'orderId',
        'remark' => 'orderId',
        'payAmount' => 'amount',
        'cardHolder' => 'nameReal',
        'bankCardNo' => 'account',
        'bankCode' => 'bank_info_id',
        'bankBranchName' => 'bank_name',
        'bankProvince' => 'province',
        'bankCity' => 'city',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => '01020000', // 工商銀行
        2 => '03010000', // 交通銀行
        3 => '01030000', // 農業銀行
        4 => '01050000', // 建設銀行
        5 => '03080000', // 招商銀行
        6 => '03050000', // 民生銀行總行
        8 => '03100000', // 上海浦東發展銀行
        9 => '04031000', // 北京银行
        10 => '03090000', // 興業銀行
        11 => '03020000', // 中信銀行
        12 => '03030000', // 光大銀行
        13 => '03040000', // 華夏銀行
        14 => '03060000', // 廣東發展銀行
        15 => '03070000', // 平安銀行
        16 => '01000000', // 中國郵政
        17 => '01040000', // 中國銀行
        222 => '04083320', // 寧波銀行
        226 => '04243010', // 南京銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'merchantId',
        'channelId',
        'notifyUrl',
        'outOrderId',
        'subject',
        'remark',
        'payAmount',
        'cardType',
        'cardHolder',
        'bankCardNo',
        'bankCode',
        'bankBranchName',
        'bankProvince',
        'bankCity',
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['defaultBank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['defaultBank'] = $this->bankMap[$this->requestData['defaultBank']];

        // 調整網銀提交網址
        $postUrl = 'https://payment.' . $this->options['postUrl'] . '/sfpay/payServlet';

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1103, 1111])) {
            $this->requestData['scanType'] = $this->requestData['defaultBank'];

            unset($this->requestData['returnUrl']);
            unset($this->requestData['channel']);
            unset($this->requestData['cardAttr']);
            unset($this->requestData['defaultBank']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            // 調整提交網址
            $postUrl = 'payment.https.payment.' . $this->options['postUrl'];

            $curlParam = [
                'method' => 'POST',
                'uri' => '/sfpay/scanCodePayServlet',
                'ip' => $this->options['verify_ip'],
                'host' => $postUrl,
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['respCode']) || !isset($parseData['respMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != '99') {
                throw new PaymentConnectionException($parseData['respMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['payCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['payCode']);

            return [];
        }

        // 調整手機支付提交參數
        if (in_array($this->options['paymentVendorId'], [1097, 1104])) {
            $this->requestData['scanType'] = $this->requestData['defaultBank'];

            unset($this->requestData['returnUrl']);
            unset($this->requestData['channel']);
            unset($this->requestData['cardAttr']);
            unset($this->requestData['defaultBank']);

            // 調整提交網址
            $postUrl = 'https://payment.' . $this->options['postUrl'] . '/sfpay/h5PayServlet';
        }

        // 調整銀聯在線、銀聯手機支付提交參數及網址
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            unset($this->requestData['returnUrl']);
            unset($this->requestData['defaultBank']);
            unset($this->requestData['channel']);
            unset($this->requestData['cardAttr']);

            $postUrl = 'https://payment.' . $this->options['postUrl'] . '/sfpay/fastUnionPayServlet';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
        ];
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 驗證出款時支付平台對外設定
        if ($withdrawHost == '') {
            throw new PaymentException('No withdraw_host specified', 150180194);
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->withdrawRequestData['bankCode'], $this->withdrawBankMap)) {
            throw new PaymentException('BankInfo is not supported by PaymentGateway', 150180195);
        }

        // 卡號須用公鑰加密
        $encrypted = '';
        openssl_public_encrypt($this->withdrawRequestData['bankCardNo'], $encrypted, $this->getRsaPublicKey());

        // 額外的參數設定
        $this->withdrawRequestData['notifyUrl'] .= 'withdraw_return.php';
        $this->withdrawRequestData['bankCardNo'] = base64_encode($encrypted);
        $this->withdrawRequestData['bankCode'] = $this->withdrawBankMap[$this->withdrawRequestData['bankCode']];

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/sfpay/agentPayServlet',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        if (!isset($parseData['respCode']) || !isset($parseData['respMsg'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // 餘額不足
        if ($parseData['respCode'] == '20') {
            throw new PaymentException('Insufficient balance', 150180197);
        }

        if ($parseData['respCode'] != '99') {
            throw new PaymentConnectionException($parseData['respMsg'], 180124, $this->getEntryId());
        }

        if (isset($parseData['localOrderId'])) {
            // 紀錄出款明細的支付平台參考編號
            $this->setCashWithdrawEntryRefId($parseData['localOrderId']);
        }
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = base64_decode($this->options['sign']);

        if (openssl_verify($encodeStr, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmt'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/order/query',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
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
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/order/query',
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
        $data = json_decode($this->options['content'], true);

        if (!isset($data['respType']) || !isset($data['respMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($data['respType'] != 'S') {
            throw new PaymentConnectionException($data['respMsg'], 180123, $this->getEntryId());
        }

        $this->trackingResultVerify($data);

        if (!isset($data['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $data) && trim($data[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $data[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = base64_decode($data['sign']);

        if (openssl_verify($encodeStr, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($data['oriRespCode'] == '99') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($data['oriRespCode'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($data['outOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($data['transAmt'] != $this->options['amount']) {
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

        // 組織加密簽名，排除sign，其他非空的參數都要納入加密
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 組織加密簽名，排除sign，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        // 組織加密簽名
        foreach ($this->withdrawEncodeParams as $key) {
            if (array_key_exists($key, $this->withdrawRequestData) && trim($this->withdrawRequestData[$key]) !== '') {
                $encodeData[$key] = $this->withdrawRequestData[$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
