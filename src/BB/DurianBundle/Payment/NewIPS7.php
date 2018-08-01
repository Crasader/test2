<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Aw\Nusoap\NusoapParser;

/**
 * 環迅支付7.0-非直連
 */
class NewIPS7 extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'MerBillNo' => '', // 商戶訂單號
        'Amount' => '', // 金額(保留兩位小數)。格式: 12.00
        'Date' => '', // 訂單日期
        'CurrencyType' => '156', // 幣種
        'GatewayType' => '01', // 支付方式。01: 借記卡
        'Lang' => 'GB', // 語言。GB: 中文
        'Merchanturl' => '', // 支付成功返回url
        'FailUrl' => '', // 支付失敗返回url
        'Attach' => '', // 商戶數據包
        'OrderEncodeType' => '5', // 訂單支付接口加密方式。5: Md5
        'RetEncodeType' => '17', // 交易返回接口加密方式。17: Md5
        'RetType' => '1', // 返回方式。1: 異步返回
        'ServerUrl' => '', // 伺服器返回url
        'BillEXP' => '', // 訂單有效日
        'GoodsName' => '', // 商品名稱
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerBillNo' => 'orderId',
        'Amount' => 'amount',
        'Date' => 'orderCreateDate',
        'Merchanturl' => 'notify_url',
        'ServerUrl' => 'notify_url',
        'GoodsName' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerBillNo',
        'Amount',
        'Date',
        'CurrencyType',
        'GatewayType',
        'Lang',
        'Merchanturl',
        'FailUrl',
        'Attach',
        'OrderEncodeType',
        'RetEncodeType',
        'RetType',
        'ServerUrl',
        'BillEXP',
        'GoodsName',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerBillNo' => 1,
        'CurrencyType' => 1,
        'Amount' => 1,
        'Date' => 1,
        'Status' => 1,
        'Msg' => 0,
        'Attach' => 0,
        'IpsBillNo' => 1,
        'IpsTradeNo' => 1,
        'BankBillNo' => 0,
        'RetEncodeType' => 1,
        'ResultType' => 0,
        'IpsBillTime' => 0,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'MerBillNo' => '', // 商戶訂單號
        'Date' => '', // 訂單日期
        'Amount' => '', // 金額(保留兩位小數)。格式: 12.00
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'MerBillNo' => 'orderId',
        'Date' => 'orderCreateDate',
        'Amount' => 'amount',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'MerBillNo',
        'Date',
        'Amount',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'MerBillNo' => 1,
        'IpsBillNo' => 1,
        'TradeType' => 1,
        'Currency' => 1,
        'Amount' => 1,
        'MerBillDate' => 1,
        'IpsBillTime' => 1,
        'Attach' => 1,
        'Status' => 1,
        'RspMsg' => 1,
        'ReqDate' => 1,
        'RspDate' => 1,
        'Signature' => 1,
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

        // 額外的參數設定
        $createAt = new \Datetime($this->requestData['Date']);
        $this->requestData['Date'] = $createAt->format('Ymd');
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);

        // 商家額外的參數設定
        $names = ['Account'];
        $extra = $this->getMerchantExtraValue($names);

        // 設定version和encoding
        $context = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8',
        ];

        // 因xml格式要求參數區分為head跟body兩個區塊，不能直接用requestData轉xml
        $xmlArray = [
            'GateWayReq' => [
                'head' => [
                    'Version' => 'v1.0.0', // 版本號
                    'MerCode' => $this->options['number'], // 商戶號
                    'MerName' => '', // 商戶名
                    'Account' => $extra['Account'], // 帳戶號
                    'MsgId' => $this->options['orderId'], // 消息編號
                    'ReqDate' => $createAt->format('YmdHis'), // 商戶請求時間
                    'Signature' => $this->encode(), // 數字簽名
                ],
                'body' => $this->requestData,
            ],
        ];

        $xml = $this->arrayToXml($xmlArray, $context, 'Ips');

        return ['pGateWayReq' => $xml];
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

        // 先驗證平台回傳的必要參數
        if (!isset($this->options['paymentResult'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 解析xml以驗證相關參數
        $parseData = $this->parseData($this->options['paymentResult']);

        if (!isset($parseData['GateWayRsp']['head'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($parseData['GateWayRsp']['body'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 驗證返回是否有RspCode
        if (!isset($parseData['GateWayRsp']['head']['RspCode'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 訂單返回異常
        if ($parseData['GateWayRsp']['head']['RspCode'] != '000000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 直接取平台回傳xml的<body>...</body>部分來組加密字串
        $match = [];
        preg_match('/<body>(.*?)<\/body>/', $this->options['paymentResult'], $match);
        $encodeStr = $match[0];

        $this->options = array_merge($parseData['GateWayRsp']['head'], $parseData['GateWayRsp']['body']);

        // 驗證返回參數
        $this->payResultVerify();

        // 額外的加密設定
        $encodeStr .= $entry['merchant_number'];
        $encodeStr .= $this->privateKey;
        $signature = md5($encodeStr);

        // 沒有返回Signature就要丟例外
        if (!isset($this->options['Signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Signature'] !== $signature) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Status'] !== 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['MerBillNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != $entry['amount']) {
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
        $date = new \DateTime($this->trackingRequestData['Date']);
        $this->trackingRequestData['Date'] = $date->format('Ymd');
        $this->trackingRequestData['Amount'] = sprintf('%.2f', $this->trackingRequestData['Amount']);

        // 商家額外的參數設定
        $names = ['Account'];
        $extra = $this->getMerchantExtraValue($names);

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定version和encoding
        $context = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8'
        ];

        // 因xml格式要求參數區分為head跟body兩個區塊，不能直接用trackingRequestData轉xml
        $xmlArray = [
            'OrderQueryReq' => [
                'head' => [
                    'Version' => 'v1.0.0', // 版本號
                    'MerCode' => $this->options['number'], // 商戶號
                    'MerName' => '', // 商戶名,
                    'Account' => $extra['Account'], // 帳戶號
                    'ReqDate' => $date->format('YmdHis'), // 商戶請求時間
                    'Signature' => $this->trackingEncode(), // 數字簽名
                ],
                'body' => $this->trackingRequestData,
            ],
        ];

        $xml = $this->arrayToXml($xmlArray, $context, 'Ips');

        $callParams = ['orderQuery' => $xml];

        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'uri' => '/psfp-entry/services/order?wsdl',
            'function' => 'getOrderByMerBillNo',
            'callParams' => $callParams,
            'wsdl' => true,
        ];

        $result = $this->soapRequest($nusoapParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($result);

        if (!isset($parseData['OrderQueryRsp']['head'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($parseData['OrderQueryRsp']['body'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證返回是否有RspCode
        if (!isset($parseData['OrderQueryRsp']['head']['RspCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢異常
        if ($parseData['OrderQueryRsp']['head']['RspCode'] != '000000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $detail = array_merge($parseData['OrderQueryRsp']['head'], $parseData['OrderQueryRsp']['body']);
        $this->trackingResultVerify($detail);

        // 直接取平台回傳xml的<body>...</body>部分來組加密字串
        $match = [];
        preg_match('/<body>(.*?)<\/body>/', $result, $match);
        $encodeStr = $match[0];

        $encodeStr .= $this->options['number'];
        $encodeStr .= $this->privateKey;
        $signature = md5($encodeStr);

        // 驗證簽名
        if ($detail['Signature'] != $signature) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單狀態不為Y則代表支付失敗
        if ($detail['Status'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($detail['Amount'] != $this->options['amount']) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $date = new \DateTime($this->trackingRequestData['Date']);
        $this->trackingRequestData['Date'] = $date->format('Ymd');
        $this->trackingRequestData['Amount'] = sprintf('%.2f', $this->trackingRequestData['Amount']);

        // 商家額外的參數設定
        $names = ['Account'];
        $extra = $this->getMerchantExtraValue($names);

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定version和encoding
        $context = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8'
        ];

        // 因xml格式要求參數區分為head跟body兩個區塊，不能直接用trackingRequestData轉xml
        $xmlArray = [
            'OrderQueryReq' => [
                'head' => [
                    'Version' => 'v1.0.0', // 版本號
                    'MerCode' => $this->options['number'], // 商戶號
                    'MerName' => '', // 商戶名
                    'Account' => $extra['Account'], // 帳戶號
                    'ReqDate' => $date->format('YmdHis'), // 商戶請求時間
                    'Signature' => $this->trackingEncode(), // 數字簽名
                ],
                'body' => $this->trackingRequestData,
            ],
        ];

        $xml = $this->arrayToXml($xmlArray, $context, 'Ips');

        $arguments = ['orderQuery' => $xml];

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/psfp-entry/services/order?wsdl',
            'function' => 'getOrderByMerBillNo',
            'arguments' => $arguments,
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

        // 預設 xml encoding 是 UTF-8
        $xmlEncoding = 'UTF-8';

        // 因 xml encoding 欄位的 html 標籤有編碼，需先解碼才能取出 xml encoding
        $htmlDecodeContent = htmlspecialchars_decode($this->options['content']);

        $matches = [];

        // 取出 xml encoding
        if (preg_match("/<\?xml.*?encoding=[\"']([^\"']*)[\"'].*?\?>/", $htmlDecodeContent, $matches)) {
            $xmlEncoding = $matches[1];
        }

        // 取出 soap 的結果
        $soapParser = new NusoapParser($this->options['content'], $xmlEncoding);
        $soapBody = $soapParser->get_soapbody();
        $result = array_shift($soapBody);

        $parseData = $this->parseData($result);

        if (!isset($parseData['OrderQueryRsp']['head'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($parseData['OrderQueryRsp']['body'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證返回是否有RspCode
        if (!isset($parseData['OrderQueryRsp']['head']['RspCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢異常
        if ($parseData['OrderQueryRsp']['head']['RspCode'] != '000000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $detail = array_merge($parseData['OrderQueryRsp']['head'], $parseData['OrderQueryRsp']['body']);
        $this->trackingResultVerify($detail);

        // 直接取平台回傳xml的<body>...</body>部分來組加密字串
        $match = [];
        preg_match('/<body>(.*?)<\/body>/', $result, $match);
        $encodeStr = $match[0];

        $encodeStr .= $this->options['number'];
        $encodeStr .= $this->privateKey;
        $signature = md5($encodeStr);

        // 驗證簽名
        if ($detail['Signature'] != $signature) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單狀態不為Y則代表支付失敗
        if ($detail['Status'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($detail['Amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 處理訂單查詢支付平台返回的編碼
     *
     * @param array $response 訂單查詢的返回
     * @return array
     */
    public function processTrackingResponseEncoding($response)
    {
        // kue 先將回傳資料先做 base64 編碼，因此需先解開
        $response['body'] = trim(base64_decode($response['body']));

        return $response;
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        // 設定version和encoding
        $context = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $body = $this->arrayToXml($encodeData, $context, 'body');
        $match = [];
        preg_match('/<body>(.*?)<\/body>/', $body, $match);
        $encodeStr = $match[0];

        // 額外的加密設定
        $encodeStr .= $this->options['number'];
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        // 設定version和encoding
        $context = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8',
        ];

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $body = $this->arrayToXml($encodeData, $context, 'body');
        $match = [];
        preg_match('/<body>(.*?)<\/body>/', $body, $match);
        $encodeStr = $match[0];

        // 額外的加密設定
        $encodeStr .= $this->options['number'];
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
        return $this->xmlToArray($content);
    }
}
