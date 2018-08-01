<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 銀盛支付
 */
class YsePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'method' => 'ysepay.online.directpay.createbyuser', // 接口名稱
        'partner_id' => '', // 商號
        'timestamp' => '', // 交易起始時間
        'charset' => 'UTF-8', // 編碼，固定值
        'sign_type' => 'RSA', // 簽名類型，固定值
        'sign' => '', // 簽名
        'notify_url' => '', // 異步通知網址
        'version' => '3.0', // 版本號，固定值
        'out_trade_no' => '', // 商戶訂單號
        'subject' => '', // 商品描述，設定username方便業主比對
        'total_amount' => '', // 訂單金額
        'seller_id' => '', // 商戶名，等於商戶號
        'seller_name' => '', // 商戶名
        'timeout_express' => '96h', // 未付款時間，固定值
        'pay_mode' => 'internetbank', // 支付模式，固定值
        'bank_type' => '', // 銀行類型
        'bank_account_type' => 'personal', // 銀行帳戶類型，固定值
        'support_card_type' => 'debit', // 銀行卡類型，固定值
        'business_code' => '', // 業務代碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner_id' => 'number',
        'out_trade_no' => 'orderId',
        'subject' => 'username',
        'total_amount' => 'amount',
        'seller_id' => 'number',
        'timestamp' => 'orderCreateDate',
        'notify_url' => 'notify_url',
        'bank_type' => 'paymentVendorId',
    ];

    /**
     * 二維支付時的業務參數
     *
     * @var array
     */
    protected $bizContent = [
        'out_trade_no',
        'subject',
        'total_amount',
        'seller_id',
        'seller_name',
        'timeout_express',
        'business_code',
        'bank_type',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'method',
        'partner_id',
        'timestamp',
        'charset',
        'sign_type',
        'notify_url',
        'version',
        'out_trade_no',
        'subject',
        'total_amount',
        'seller_id',
        'seller_name',
        'timeout_express',
        'pay_mode',
        'bank_type',
        'bank_account_type',
        'support_card_type',
        'business_code',
        'biz_content',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'sign_type' => 1,
        'notify_type' => 1,
        'notify_time' => 1,
        'out_trade_no' => 1,
        'total_amount' => 1,
        'trade_no' => 0,
        'trade_status' => 1,
        'account_date' => 0,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'method' => 'ysepay.online.trade.query', // 接口名稱
        'partner_id' => '', // 商號
        'timestamp' => '', // 交易起始時間
        'charset' => 'UTF-8', // 編碼，固定值
        'sign_type' => 'RSA', // 簽名類型，固定值
        'sign' => '', // 簽名
        'version' => '3.0', // 版本號，固定值
        'biz_content' => '', // 業務參數
        'out_trade_no' => '', // 商戶訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partner_id' => 'number',
        'timestamp' => 'orderCreateDate',
        'out_trade_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'method',
        'partner_id',
        'timestamp',
        'charset',
        'sign_type',
        'version',
        'biz_content',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'out_trade_no' => 1,
        'trade_no' => 1,
        'trade_status' => 1,
        'total_amount' => 1,
        'receipt_amount' => 0,
        'account_date' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1021000', // 中國工商銀行
        '2' => '3012900', // 交通銀行
        '3' => '1031000', // 中國農業銀行
        '4' => '1051000', // 中國建設銀行
        '5' => '3085840', // 招商銀行
        '6' => '3051000', // 中國民生銀行
        '8' => '3102900', // 上海浦東發展銀行
        '9' => '3131000', // 北京銀行
        '10' => '3093910', // 興業銀行
        '11' => '3021000', // 中信銀行
        '12' => '3031000', // 中國光大銀行
        '14' => '3065810', // 廣東發展銀行
        '15' => '3071000', // 平安銀行
        '16' => '4031000', // 中國郵政
        '17' => '1041000', // 中國銀行
        '222' => '3133320', // 寧波银行
        '223' => '5021000', // 東亞银行
        '226' => '3133010', // 南京银行
        '228' => '3222900', // 上海農村商業银行
        '1090' => '1902000', // 微信二維
        '1092' => '1903000 ', // 支付寶二維
        '1103' => '1904000', // QQ二維
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
        if (!array_key_exists($this->requestData['bank_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['seller_name', 'business_code']);

        // 額外的參數設定
        $this->requestData['seller_name'] = $merchantExtraValues['seller_name'];
        $this->requestData['business_code'] = $merchantExtraValues['business_code'];
        $this->requestData['bank_type'] = $this->bankMap[$this->requestData['bank_type']];
        $this->requestData['total_amount'] = sprintf('%.2f', $this->requestData['total_amount']);

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            $this->requestData['method'] = 'ysepay.online.qrcodepay';

            // 設定業務參數
            $this->requestData['biz_content'] = $this->getBizContent();

            $removeParams = [
                'out_trade_no',
                'subject',
                'total_amount',
                'seller_id',
                'seller_name',
                'timeout_express',
                'pay_mode',
                'bank_type',
                'bank_account_type',
                'support_card_type',
                'business_code',
            ];

            // 移除二維不需要以及組成業務參數的參數
            foreach ($removeParams as $removeParam) {
                unset($this->requestData[$removeParam]);
                $encodeParamsKey = array_search($removeParam, $this->encodeParams);
                unset($this->encodeParams[$encodeParamsKey]);
            }

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            // 調整二維提交網址
            $postUrl = 'payment.https.qrcode.' . $this->options['postUrl'];

            $curlParam = [
                'method' => 'POST',
                'uri' => '/gateway.do',
                'ip' => $this->options['verify_ip'],
                'host' => $postUrl,
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['sign'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if (!isset($parseData['ysepay_online_qrcodepay_response'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $response = $parseData['ysepay_online_qrcodepay_response'];

            $encodeStr = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $sign = base64_decode($parseData['sign']);

            if (openssl_verify($encodeStr, $sign, $this->getRsaPublicKey()) !== 1) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }

            if (!isset($response['code'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($response['code'] != '10000' && isset($response['sub_msg'])) {
                throw new PaymentConnectionException($response['sub_msg'], 180130, $this->getEntryId());
            }

            if (!isset($response['source_qr_code_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($response['source_qr_code_url']);

            return [];
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 調整網銀提交網址
        $postUrl = 'https://openapi.' . $this->options['postUrl'] . '/gateway.do';

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
        ];
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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = base64_decode($this->options['sign']);

        if (openssl_verify($encodeStr, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'TRADE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_amount'] != $entry['amount']) {
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

        $bizContent = ['out_trade_no' => $this->trackingRequestData['out_trade_no']];

        // 設定查詢時的業務參數
        $this->trackingRequestData['biz_content'] = json_encode($bizContent);

        unset($this->trackingRequestData['out_trade_no']);

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/gateway.do',
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $bizContent = ['out_trade_no' => $this->trackingRequestData['out_trade_no']];

        // 設定查詢時的業務參數
        $this->trackingRequestData['biz_content'] = json_encode($bizContent);

        unset($this->trackingRequestData['out_trade_no']);

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/gateway.do',
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

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($parseData['ysepay_online_trade_query_response'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $response = $parseData['ysepay_online_trade_query_response'];

        $encodeStr = json_encode($response, JSON_UNESCAPED_UNICODE);

        $sign = base64_decode($parseData['sign']);

        if (openssl_verify($encodeStr, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($response['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($response['code'] != '10000' && isset($response['sub_msg'])) {
            throw new PaymentConnectionException($response['sub_msg'], 180123, $this->getEntryId());
        }

        $this->trackingResultVerify($response);

        if ($response['trade_status'] == 'WAIT_BUYER_PAY') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($response['trade_status'] != 'TRADE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 查詢成功返回商戶訂單號
        if ($response['out_trade_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 查詢成功返回訂單金額
        if ($response['total_amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 回傳RSA公鑰
     *
     * @return resource
     */
    public function getRsaPublicKey()
    {
        $content = $this->options['rsa_public_key'];

        if (!$content) {
            throw new PaymentException('Rsa public key is empty', 180095);
        }

        $cert = sprintf(
            '%s%s%s',
            "-----BEGIN CERTIFICATE-----\n",
            chunk_split($content, 64, "\n"),
            "-----END CERTIFICATE-----\n"
        );

        $publicKey = openssl_pkey_get_public($cert);

        if (!$publicKey) {
            throw new PaymentException('Get rsa public key failure', 180096);
        }

        return $publicKey;
    }

    /**
     * 回傳私鑰
     *
     * @return resource
     */
    public function getRsaPrivateKey()
    {
        // privateKey為解開RSA私鑰的口令，所以要驗證
        $this->verifyPrivateKey();

        $passphrase = $this->privateKey;

        $content = base64_decode($this->options['rsa_private_key']);

        if (!$content) {
            throw new PaymentException('Rsa private key is empty', 180092);
        }

        $privateCert = [];
        $status = openssl_pkcs12_read($content, $privateCert, $passphrase);

        if (!$status) {
            throw new PaymentException('Get rsa private key failure', 180093);
        }

        return $privateCert['pkey'];
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 設定支付時的業務參數
     */
    private function getBizContent()
    {
        $bizContentData = [];

        // 業務參數
        foreach ($this->bizContent as $index) {
            $bizContentData[$index] = $this->requestData[$index];
        }

        return json_encode($bizContentData);
    }
}
