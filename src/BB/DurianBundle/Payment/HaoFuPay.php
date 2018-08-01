<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 好付支付
 */
class HaoFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantID' => '', // 商戶號
        'version' => '1.0', // 網關版本(固定值)
        'inputCharset' => '1', // 參數編碼(1 為 UTF-8)
        'signType' => '1', // 簽名類型(1 為 RSA)
        'pageUrl' => '', // 前台跳轉網址
        'noticeUrl' => '', // 後臺返回網址
        'payerName' => '', // 收貨人姓名(可空)
        'payerAddress' => '', // 收貨人地址(可空)
        'payerZip' => '', // 收貨人郵編(可空)
        'payerContact' => '', // 收貨人電話(可空)
        'payerEmail' => '', // 收貨人郵件(可空)
        'orderId' => '', // 訂單號
        'orderAmount' => '', // 訂單金額(單位為分)
        'orderTime' => '', // 訂單時間
        'productName' => '', // 商品名稱(存 username 方便業主對帳)
        'productNum' => '1', // 商品數量(必填)
        'productDesc' => '', // 商品描述(可空)
        'payType' => '', // 支付銀行
        'userIp' => '', // 用戶IP(可空)
        'ext1' => '', // 擴展字段1(可空)
        'ext2' => '', // 擴展字段2(可空)
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantID' => 'number',
        'pageUrl' => 'notify_url',
        'noticeUrl' => 'notify_url',
        'orderId' => 'orderId',
        'orderAmount' => 'amount',
        'orderTime' => 'orderCreateDate',
        'productName' => 'username',
        'payType' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantID',
        'version',
        'inputCharset',
        'signType',
        'pageUrl',
        'noticeUrl',
        'orderId',
        'orderAmount',
        'orderTime',
        'productName',
        'productNum',
        'payType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantID' => 1,
        'version' => 1,
        'signType' => 1,
        'orderId' => 1,
        'orderAmount' => 1,
        'orderTime' => 1,
        'dealId' => 1,
        'dealTime' => 1,
        'payAmount' => 1,
        'ext1' => 0,
        'ext2' => 0,
        'payResult' => 1,
        'errMsg' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '<result>SUCCESS</result>';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SZPAB', // 平安银行
        '16' => 'PSBC', // 中國郵政儲蓄
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '219' => 'GZCB', // 廣州銀行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'HKBEA', // 東亞銀行
        '224' => 'WZCB', // 溫州銀行
        '225' => 'SXJS', // 晉商銀行
        '226' => 'NJCB', // 南京銀行
        '227' => 'GNXS', // 廣州市農信社
        '228' => 'SHRCB', // 上海市農商行
        '229' => 'HKBCHINA', // 漢口銀行
        '230' => 'ZHNX', // 珠海市農村信用聯
        '231' => 'SDE', // 順德農信社
        '232' => 'YDXH', // 堯都信用合作聯社
        '312' => 'BOCD', // 成都銀行
        '1090' => 'WEIXIN', // 微信二維
        '1092' => 'ALIPAY', // 支付寶二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantID' => '', // 商戶號
        'method' => 'queryOrder', // 查詢方法(固定值)
        'version' => '1.0', // 網關版本(固定值)
        'signType' => '1', // 簽名類型(1 為 RSA)
        'sign' => '', // 簽名
        'orderId' => '', // 訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantID' => 'number',
        'orderId' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantID',
        'method',
        'version',
        'signType',
        'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'orderId' => 1,
        'orderAmount' => 1,
        'dealId' => 1,
        'dealTime' => 1,
        'payResult' => 1,
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

        // 檢查銀行是否支援
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $date = new \Datetime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $date->format('YmdHis');
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);

        // 產生加密字串
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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 檢查簽名
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 驗簽
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options) && trim($this->options[$key]) !== '') {
                $encodeData[$key] = $this->options[$key];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        $signVerify = openssl_verify($encodeStr, base64_decode($this->options['sign']), $this->getRsaPublicKey());

        if ($signVerify !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 支付失敗且有錯誤訊息
        if ($this->options['payResult'] != '10' && isset($this->options['errMsg'])) {
            throw new PaymentConnectionException($this->options['errMsg'], 180130, $this->getEntryId());
        }

        // 支付失敗
        if ($this->options['payResult'] != '10') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($this->options['payAmount'] != round($entry['amount'] * 100)) {
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

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/query',
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
        $this->trackingVerify();
        $this->verifyPrivateKey();

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
            'path' => '/query',
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

        // 檢查錯誤代碼及訊息
        if (!isset($parseData['err']) || !isset($parseData['msg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['err'] != '0') {
            throw new PaymentConnectionException($parseData['msg'], 180123, $this->getEntryId());
        }

        // 成功查詢才會返回訂單內容
        if (!isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 檢查必要回傳參數
        $this->trackingResultVerify($parseData['data']);

        // 訂單處理結果 10 為支付成功
        if ($parseData['data']['payResult'] != 10) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($parseData['data']['orderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($parseData['data']['orderAmount'] != round($this->options['amount'] * 100)) {
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
            if (trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
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
        foreach ($this->trackingEncodeParams as $index) {
            if (trim($this->trackingRequestData[$index]) !== '') {
                $encodeData[$index] = $this->trackingRequestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
