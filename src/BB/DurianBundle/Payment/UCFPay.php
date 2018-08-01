<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 先鋒支付
 *
 */
class UCFPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'accountType' => '1', //卡種 1:借記卡 2:信用卡 3:借貸不區分
        'amount' => '', //金額，以分為單位
        'bankId' => '', //銀行編碼
        'expireTime' => '', //訂單過時間(YmdHis)，非必填
        'memo' => '', //保留域，非必填
        'merchantId' => '', //商戶代碼(商號)
        'merchantNo' => '', //商戶訂單號
        'noticeUrl' => '', //後台通知地址
        'payerId' => '', //付款方ID，非必填
        'productInfo' => '', //商品信息，非必填
        'productName' => 'productName', //商品名稱，因為必填欄位，預設為productName
        'returnUrl' => '', //前台通知地址
        'secId' => 'MD5', //簽名算法
        'service' => 'REQ_PAY_BANK', //接口名稱
        'source' => '1', //來源 1:PC 2:Mobile
        'token' => '', //口令
        'transCur' => '156', //幣種 目前只支持人民幣:156
        'userType' => '', //用戶類型，非必填。1:個人 2:企業
        'version' => '1.0.0', //接口版本
        'sign' => '' //訂單簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'merchantNo' => 'orderId',
        'bankId' => 'paymentVendorId',
        'amount' => 'amount',
        'noticeUrl' => 'notify_url',
        'returnUrl' => 'notify_url'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'accountType',
        'amount',
        'bankId',
        'expireTime',
        'memo',
        'merchantId',
        'merchantNo',
        'noticeUrl',
        'payerId' ,
        'productInfo',
        'productName',
        'returnUrl',
        'secId' ,
        'service',
        'source',
        'token',
        'transCur',
        'userType',
        'version'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantId' => 1,
        'merchantNo' => 1,
        'amount' => 1,
        'transCur' => 1,
        'memo' => 1,
        'tradeNo' => 1,
        'status' => 1,
        'tradeTime' => 1
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'merchantNo' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantId',
        'merchantNo',
        'secId',
        'service',
        'token',
        'version'
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', //商戶代碼
        'merchantNo' => '', //商戶訂單號
        'secId' => 'MD5', //簽名算法
        'service' => 'REQ_ORDER_QUERY_BY_ID', //接口名稱
        'token' => '', //口令
        'version' => '1.0.0', //接口版本
        'sign' => '' //訂單簽名數據
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchantId' => 1,
        'merchantNo' => 1,
        'amount' => 1,
        'transCur' => 1,
        'memo' => 1,
        'tradeNo' => 1,
        'status' => 1,
        'tradeTime' => 1
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
        '1' => 'ICBC', //中國工商銀行
        '2' => 'BOCOM', //交通銀行
        '3' => 'ABC', //農業銀行
        '4' => 'CCB', //中國建設銀行
        '5' => 'CMB', //招商銀行
        '6' => 'CMBC', //中國民生銀行
        '7' => 'SDB', //深圳發展銀行
        '8' => 'SPDB', //上海浦東發展銀行
        '9' => 'BCCB', //北京銀行
        '10' => 'CIB', //興業銀行
        '11' => 'CNCB', //中信銀行
        '12' => 'CEB', //中國光大銀行
        '13' => 'HXB', //華夏銀行
        '14' => 'GDB', //廣東發展銀行
        '15' => 'PAB', //平安银行
        '16' => 'PSBC', //中國郵政儲蓄銀行
        '17' => 'BOC', //中國銀行
        '19' => 'BOS', //上海銀行
        '217' => 'BOHC', //渤海銀行
        '228' => 'SRCB', //上海農村商業銀行
        '230' => 'ZHNX', //珠海市農村信用合作聯
        '278' => 'UPOP' //中國銀聯
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

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['bankId'] = $this->bankMap[$this->requestData['bankId']];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);

        // get token
        $params = [
            'merchantId' => $this->options['number'],
            'reqId' => $this->options['orderId'],
            'secId' =>'MD5',
            'service' => 'REQ_GET_TOKEN',
            'version' => '1.0.0'
        ];

        $encodeStr = urldecode(http_build_query($params)) . $this->privateKey;

        $sign = md5($encodeStr);
        $params['sign'] = $sign;

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        //https://mapi.ucfpay.com/gateway.do
        $curlParam = [
            'method' => 'GET',
            'uri' => '/gateway.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($params),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);

        $res = json_decode($result, true);

        //輸出結果為不成功，則把對外的結果(JSON)丟例外
        if (trim($res['info']) !== 'SUCCESS') {
            throw new PaymentConnectionException($result, 180130, $this->getEntryId());
        }

        //若沒有result代表取token異常
        if (!isset($res['result']) || trim($res['result']) === '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->requestData['token'] = $res['result'];

        //設定支付平台需要的加密串
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

        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        //如果沒有返回簽名擋要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (trim($this->options['status']) == 'I') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if (trim($this->options['status']) !== 'S') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchantNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $params = [
            'merchantId' => $this->options['number'],
            'reqId' => $this->options['orderId'],
            'secId' =>'MD5',
            'service' => 'REQ_GET_TOKEN',
            'version' => '1.0.0'
        ];
        $encodeStr = urldecode(http_build_query($params)) . $this->privateKey;
        $sign = md5($encodeStr);
        $params['sign'] = $sign;

        // https://mapi.ucfpay.com/gateway.do
        $tokenCurlParam = [
            'method' => 'GET',
            'uri' => '/gateway.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($params),
            'header' => []
        ];
        $tokenResult = $this->curlRequest($tokenCurlParam);
        $token = json_decode($tokenResult, true);

        // 輸出結果為不成功，則把對外的結果(JSON)丟例外
        if (trim($token['info']) !== 'SUCCESS') {
            throw new PaymentConnectionException($tokenResult, 180123, $this->getEntryId());
        }

        // 若沒有result代表取token異常
        if (!isset($token['result']) || trim($token['result']) === '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->trackingRequestData['token'] = $token['result'];
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/gateway.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);
        $this->trackingResultVerify($parseData);

        if (trim($parseData['status']) == 'I') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['status'] != 'S') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['amount'] != round($this->options['amount'] * 100)) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
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

        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 入款查詢時使用，用來分解訂單查詢時回傳的json格式
     *
     * @param string $content json格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        $parseData = json_decode(urldecode($content), true);

        return $parseData;
    }
}
