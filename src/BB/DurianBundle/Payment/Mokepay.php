<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 惠付寶支付
 */
class Mokepay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'apiName' => 'WEB_PAY_B2C', // 接口名字
        'apiVersion' => '1.0.0.0', // 接口版本
        'platformID' => '', // 平台號ID
        'merchNo' => '', // 商號
        'orderNo' => '', // 訂單號
        'tradeDate' => '', // 交易日期(格式：Ymd)
        'amt' => '', // 支付金額，保留小數點兩位，單位：元
        'merchUrl' => '', // 支付結果通知網址, 不能串參數
        'merchParam' => '', // 商戶參數
        'tradeSummary' => '', // 交易摘要，不可為空，顯示在後台，設定username方便業主比對
        'signMsg' => '', // 加密簽名
        'bankCode' => '', // 銀行代碼，不納入加密簽名
        'customerIP' => '', // 客戶端 IP(網銀不納簽，二維要)
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchNo' => 'number',
        'orderNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amt' => 'amount',
        'merchUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'tradeSummary' => 'username',
        'customerIP' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'apiName',
        'apiVersion',
        'platformID',
        'merchNo',
        'orderNo',
        'tradeDate',
        'amt',
        'merchUrl',
        'merchParam',
        'tradeSummary'
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'apiName' => 1,
        'notifyTime' => 1,
        'tradeAmt' => 1,
        'merchNo' => 1,
        'merchParam' => 1,
        'orderNo' => 1,
        'tradeDate' => 1,
        'accNo' => 1,
        'accDate' => 1,
        'orderStatus' => 1
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
        1 => 'ICBC', // 工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        278 => 'MOBI_PAY_B2C', // 銀聯在線
        1088 => 'MOBI_PAY_B2C', // 銀聯在線手機支付
        1090 => 'WECHAT_PAY', // 微信二維(直連)
        1092 => 'ALIPY_PAY', // 支付寶二維(直連)
        1103 => 'QQ_PAY', // QQ錢包二維(直連)
        1104 => 'QQ_APP_PAY', // QQ手機支付
        1107 => 'JD_PAY', // 京東錢包二維(直連)
        1111 => 'YL_PAY', // 銀聯二維(直連)
        1120 => 'MOBI_YL_B2C', // 銀聯手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'apiName' => 'MOBO_TRAN_QUERY', // 接口名字
        'apiVersion' => '1.0.0.0', // 接口版本
        'platformID' => '', // 商戶ID
        'merchNo' => '', // 商戶帳號
        'orderNo' => '', // 商戶訂單號
        'tradeDate' => '', // 交易日期
        'amt' => '', // 交易金額
        'signMsg' => '' // 加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchNo' => 'number',
        'orderNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amt' => 'amount'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'apiName',
        'apiVersion',
        'platformID',
        'merchNo',
        'orderNo',
        'tradeDate',
        'amt'
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
        'respDesc' => 1,
        'orderDate' => 1,
        'accDate' => 1,
        'orderNo' => 1,
        'accNo' => 1,
        'Status' => 1
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['amt'] = sprintf('%.2f', $this->requestData['amt']);
        $this->requestData['merchParam'] = sprintf(
            '%s_%s',
            $this->options['merchantId'],
            $this->options['domain']
        );
        $createAt = new \Datetime($this->requestData['tradeDate']);
        $this->requestData['tradeDate'] = $createAt->format('Ymd');

        // 商家額外的參數設定
        $names = ['platformID'];
        $extra = $this->getMerchantExtraValue($names);
        $this->requestData['platformID'] = $extra['platformID'];

        // 銀聯在線、銀聯在線手機支付、QQ手機支付調整提交參數
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1104, 1120])) {
            $this->requestData['apiName'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);
            unset($this->requestData['customerIP']);
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            // 修改需要提交和加密的參數
            $this->requestData['apiName'] = $this->requestData['bankCode'];
            $this->encodeParams[] = 'customerIP';

            unset($this->requestData['bankCode']);

            $this->requestData['signMsg'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/cgi-bin/netpayment/pay_gate.cgi',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = $this->xmlToArray($result);

            // 檢查必要回傳
            if (!isset($parseData['respData']['respCode']) ||
                !isset($parseData['respData']['respDesc']) ||
                !isset($parseData['signMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // 組織加密串
            $match = [];
            preg_match('/<respData>.*?<\/respData>/', $result, $match);
            $encodeStr = $match[0] . $this->privateKey;

            // 驗證簽名
            if (strcasecmp($parseData['signMsg'], md5($encodeStr)) != 0) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }

            // 返回 307 表示商戶沒開通二維直連
            if ($parseData['respData']['respCode'] == '307') {
                throw new PaymentException('Qrcode not support', 150180190);
            }

            // 返回非 00 都為失敗
            if ($parseData['respData']['respCode'] != '00') {
                throw new PaymentConnectionException($parseData['respData']['respDesc'], 180130, $this->getEntryId());
            }

            // 未返回 qrcode 網址
            if (!isset($parseData['respData']['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode(base64_decode($parseData['respData']['codeUrl']));

            return [];
        }

        // 設定加密簽名
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有返回signMsg就要丟例外
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signMsg'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tradeAmt'] != $entry['amount']) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $createAt = new \Datetime($this->trackingRequestData['tradeDate']);
        $this->trackingRequestData['tradeDate'] = $createAt->format('Ymd');
        $this->trackingRequestData['amt'] = sprintf('%.2f', $this->trackingRequestData['amt']);

        // 商家額外的參數設定
        $names = ['platformID'];
        $extra = $this->getMerchantExtraValue($names);
        $this->trackingRequestData['platformID'] = $extra['platformID'];

        // 設定加密簽名
        $this->trackingRequestData['signMsg'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/cgi-bin/netpayment/pay_gate.cgi',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($result);

        // 訂單不存在
        $message = '查询订单信息不存在[订单信息不存在]';

        if (isset($parseData['respData']['respDesc']) && $parseData['respData']['respDesc'] == $message) {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單查詢異常
        if (isset($parseData['respData']['respCode']) && $parseData['respData']['respCode'] != '00') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData['respData']);

        // 組織加密串
        $encodeStr = $parseData['data'];
        $encodeStr .= $this->privateKey;

        // 驗證簽名
        if (strcasecmp($parseData['signMsg'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單狀態為0則代表未支付
        if ($parseData['respData']['Status'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單狀態不為1則代表支付失敗
        if ($parseData['respData']['Status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 解析訂單查詢結果
     *
     * @param string $content
     * @return array
     */
    private function parseData($content)
    {
        $parseData = $this->xmlToArray($content);

        if (!isset($parseData['respData'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($parseData['signMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $match = [];
        preg_match('/<respData>(.*?)<\/respData>/', $content, $match);
        $parseData['data'] = $match[0];

        return $parseData;
    }
}
