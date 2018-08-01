<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 掌托支付
 */
class ZTuoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '3.0', // 版本號
        'method' => 'ZT.online.interface', // 接口名稱
        'partner' => '', // 商戶ID
        'banktype' => '', // 銀行類型
        'paymoney' => '', // 金額，單位元，小數點後兩位
        'ordernumber' => '', // 商戶訂單號
        'callbackurl' => '', // 異步通知地址
        'hrefbackurl' => '', // 同步通知地址，非必填
        'attach' => '', // 備註訊息，非必填
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'banktype' => 'paymentVendorId',
        'paymoney' => 'amount',
        'ordernumber' => 'orderId',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'method',
        'partner',
        'banktype',
        'paymoney',
        'ordernumber',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'partner' => 1,
        'ordernumber' => 1,
        'orderstatus' => 1,
        'paymoney' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CTTIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣發銀行
        '15' => 'PINGANBANK', // 平安銀行
        '16' => 'PSBS', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'HKBEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海農商銀行
        '233' => 'CZB', // 浙江稠州商業銀行
        '234' => 'BJRCB', // 北京農村商業銀行
        '278' => 'QUICKPAY', // 銀聯在線(快捷)
        '1088' => 'QUICKPAYWAP', // 銀聯在線_手機支付(快捷)
        '1090' => 'WEIXIN', // 微信
        '1092' => 'ALIPAY', // 支付寶
        '1097' => 'WEIXINWAP', // 微信_手機支付
        '1098' => 'ALIPAYWAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQWAP', // QQ_手機支付
        '1107' => 'JD', // 京東_二維
        '1108' => 'JDWAP', // 京東_手機支付
        '1109' => 'BAIDU', // 百度_二維
        '1110' => 'BAIDUWAP', // 百度_手機支付
        '1111' => 'UNIONPAY', // 銀聯_二維
        '1120' => 'UNIONPAYWAP', // 銀聯_手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '3.0', // 版本號
        'method' => 'ZT.online.query', // 接口名稱
        'partner' => '', // 商戶ID
        'ordernumber' => '', // 商戶訂單號
        'sysnumber' => '', // 掌托訂單號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'method',
        'partner',
        'ordernumber',
        'sysnumber',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partner' => 'number',
        'ordernumber' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'version' => 1,
        'partner' => 1,
        'ordernumber' => 1,
        'sysnumber' => 1,
        'status' => 1,
        'tradestate' => 1,
        'paymoney' => 1,
        'banktype' => 1,
        'paytime' => 1,
        'endtime' => 1,
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
        if (!array_key_exists($this->requestData['banktype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['paymoney'] = sprintf('%.2f', $this->requestData['paymoney']);
        $this->requestData['banktype'] = $this->bankMap[$this->requestData['banktype']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1109, 1111])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/online/gateway.html',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['status'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['status'] == '0' && isset($parseData['message'])) {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if ($parseData['status'] != '1') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            $signParams = [
                'version',
                'status',
                'ordernumber',
                'paymoney',
            ];

            $encodeData = [];

            // 組織加密串
            foreach ($signParams as $paymentKey) {
                if (array_key_exists($paymentKey, $parseData)) {
                    $encodeData[$paymentKey] = $parseData[$paymentKey];
                }
            }

            $encodeStr = urldecode(http_build_query($encodeData));

            $encodeStr .= $this->privateKey;

            if (!isset($parseData['sign'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['sign'] != md5($encodeStr)) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }

            if (!isset($parseData['qrurl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrurl']);

            return [];
        }

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

        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderstatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ordernumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paymoney'] != $entry['amount']) {
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
            'uri' => '/online/gateway.html',
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

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/online/gateway.html',
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

        if (!isset($parseData['status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['status'] != '1' && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180123, $this->getEntryId());
        }

        if ($parseData['status'] != '1') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['tradestate'] === '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['tradestate'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['ordernumber'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['paymoney'] != $this->options['amount']) {
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

        $encodeData['key'] = $this->privateKey;

        $encodeStr = http_build_query($encodeData);

        return md5($encodeStr);
    }
}
