<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 商銀信
 */
class ShangYinXin extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'directPay', // 接口名稱，固定值。
        'merchantId' => '', // 商戶號
        'notifyUrl' => '', // 同步通知url
        'returnUrl' => '', // 返回url
        'signType' => 'MD5', // 簽名類型
        'inputCharset' => 'UTF-8', // 參數編碼
        'outOrderId' => '', // 訂單號
        'subject' => '', // 商品名稱，不可空
        'body' => 'body', // 商品描述，不可空。
        'transAmt' => '', // 交易金額
        'payMethod' => 'bankPay', // 支付方式(bankPay: 網銀直連, default_wechat: 微信, default_alipay: 支付寶)
        'defaultBank' => '', // 網銀
        'channel' => 'B2C', // 銀行渠道。B2C:個人
        'cardAttr' => '01', // 卡類型。01:借記卡
        'ip' => '', // 終端ip
        'sign' => '', // 簽名值
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'outOrderId' => 'orderId',
        'subject' => 'orderId',
        'transAmt' => 'amount',
        'notifyUrl' => 'notify_url',
        'defaultBank' => 'paymentVendorId',
        'ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'merchantId',
        'notifyUrl',
        'inputCharset',
        'outOrderId',
        'subject',
        'body',
        'transAmt',
        'payMethod',
        'defaultBank',
        'channel',
        'cardAttr',
        'ip',
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
        15 => 'SPABANK', // 深圳平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        220 => 'HZCB', // 杭州銀行
        222 => 'NBBANK', // 寧波銀行
        226 => 'NJCB', // 南京銀行
        234 => 'BJRCB', // 北京農商銀行
        1090 => '', // 微信_二維
        1092 => '', // 支付寶_二維
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'notifyId' => 1,
        'notifyTime' => 1,
        'outOrderId' => 1,
        'subject' => 0,
        'body' => 0,
        'transAmt' => 1,
        'tradeStatus' => 1,
        'merchantId' => 1,
        'outAcctId' => 0,
        'localOrderId' => 0,
        'transTime' => 0,
        'inputCharset' => 1,
        'errorMessage' => 0,
        'financialId' => 0,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'service' => 'orderQuery', // 接口名稱
        'inputCharset' => 'utf-8', // 參數編碼
        'signType' => 'MD5', // 簽名類型
        'sign' => '', // 簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'outOrderId' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'tranAmt' => 1,
        'srcOutOrderId' => 1,
        'payOrderId' => 1,
        'payStatus' => 1,
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
        if (!array_key_exists($this->requestData['defaultBank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['defaultBank'] = $this->bankMap[$this->requestData['defaultBank']];
        $this->requestData['transAmt'] = sprintf('%.2f', $this->requestData['transAmt']);

        // 二維支付(微信、支付寶)
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            // 微信
            if ($this->options['paymentVendorId'] == '1090') {
                $this->requestData['payMethod'] = 'default_wechat';
            }

            // 支付寶
            if ($this->options['paymentVendorId'] == '1092') {
                $this->requestData['payMethod'] = 'default_alipay';
            }

            $removeParams = [
                'defaultBank',
                'channel',
                'cardAttr',
            ];

            foreach ($removeParams as $removeParam) {
                unset($this->requestData[$removeParam]);
                $encodeParamsKey = array_search($removeParam, $this->encodeParams);
                unset($this->encodeParams[$encodeParamsKey]);
            }

            $this->requestData['sign'] = $this->encode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/olgateway/scan/scanPay.htm',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0',
                ],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = $this->xmlToArray($result);

            if (!isset($parseData['reCode']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['reCode'] != 'SUCCESS') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['payCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['payCode']);

            return [];
        }

        // 網銀
        unset($this->requestData['ip']);
        $encodeParamsKey = array_search('ip', $this->encodeParams);
        unset($this->encodeParams[$encodeParamsKey]);

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        /**
         * 驗證是不是商銀信返回的
         * url: https://paymenta.allscore.com/olgateway/noticeQuery.htm
         */
        $param = [
            'merchantId' => $entry['merchant_number'],
            'notifyId' => $this->options['notifyId'],
        ];

        $curlParam = [
            'method' => 'GET',
            'uri' => '/olgateway/noticeQuery.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($param),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 如果是invalid，代表傳入的參數無效
        if (trim($result) == 'invalid') {
            throw new PaymentConnectionException('Invalid pay parameters', 180129, $this->getEntryId());
        }

        // 如果不是true，代表回傳的結果有異常
        if (trim($result) != 'true') {
            throw new PaymentConnectionException('Invalid response', 180148, $this->getEntryId());
        }

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        // privateKey的值接在最後面
        $encodeStr .= $this->privateKey;

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] != '2') {
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/olgateway/orderQuery.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['pays']['pay'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['pays']['pay']);

        if ($parseData['pays']['pay']['payStatus'] != 'ORDER_STATUS_SUC') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['pays']['pay']['srcOutOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['pays']['pay']['tranAmt'] != $this->options['amount']) {
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

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/olgateway/orderQuery.htm?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
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

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($this->options['content']);

        if (!isset($parseData['pays']['pay'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['pays']['pay']);

        if ($parseData['pays']['pay']['payStatus'] != 'ORDER_STATUS_SUC') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['pays']['pay']['srcOutOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['pays']['pay']['tranAmt'] != $this->options['amount']) {
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

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
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

        // 組織加密簽名，排除sign和signType，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && $key != 'signType' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
