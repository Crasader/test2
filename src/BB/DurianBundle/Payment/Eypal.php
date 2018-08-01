<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 盈寶支付
 */
class Eypal extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0', // 版本號
        'partner' => '', // 商家ID
        'orderid' => '', // 訂單號
        'payamount' => '', // 訂單金額(小數點後兩位)
        'payip' => '', // 用戶IP
        'notifyurl' => '', // 異步通知
        'returnurl' => '', // 同步通知
        'paytype' => '', // 支付類型
        'remark' => '', // 商家自定義
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'orderid' => 'orderId',
        'payamount' => 'amount',
        'payip' => 'ip',
        'notifyurl' => 'notify_url',
        'returnurl' => 'notify_url',
        'paytype' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'partner',
        'orderid',
        'payamount',
        'payip',
        'notifyurl',
        'returnurl',
        'paytype',
        'remark',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'partner' => 1,
        'orderid' => 1,
        'payamount' => 1,
        'opstate' => 1,
        'orderno' => 1,
        'eypaltime' => 1,
        'message' => 1,
        'paytype' => 1,
        'remark' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CTITC', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'CGB', // 廣東發展銀行
        15 => 'SDB', // 深圳平安銀行
        16 => 'PSBC', // 中國郵政儲蓄
        17 => 'BOC', // 中國銀行
        19 => 'SHBANK', // 上海銀行
        217 => 'BOHAI', // 渤海銀行
        228 => 'SHNS', // 上海農村商業銀行
        278 => 'UNION', // 銀聯支付
        1090 => 'WECHAT', // 微信支付
        1092 => 'ALIPAY', // 支付寶_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '1.0', // 版本號
        'partner' => '', // 商家ID
        'orderid' => '', // 訂單號
        'remark' => '', // 商家自定義，可空
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partner' => 'number',
        'orderid' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'partner',
        'orderid',
        'remark',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'partner' => 1,
        'orderid' => 1,
        'payamount' => 1,
        'opstate' => 1,
        'orderno' => 0,
        'eypaltime' => 1,
        'message' => 1,
        'paytype' => 1,
        'remark' => 0,
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
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];
        $this->requestData['payamount'] = sprintf('%.2f', $this->requestData['payamount']);
        $this->requestData['remark'] = sprintf(
            '%s_%s',
            $this->options['merchantId'],
            $this->options['domain']
        );

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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        //如果沒有返回簽名擋要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtolower(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['opstate'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['opstate'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payamount'] != $entry['amount']) {
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

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Query/Gateway',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        $data = $this->parseData($result);

        $this->trackingResultVerify($data);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $index) {
            if (array_key_exists($index, $data)) {
                $encodeStr .= $index . '=' . $data[$index] . '|';
            }
        }

        $encodeStr .= 'key=' . $this->privateKey;

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($data['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($data['sign'] != strtolower(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($data['opstate'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($data['opstate'] != '2') {
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

        foreach ($this->encodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->requestData[$paymentKey];
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtolower(md5($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        foreach ($this->trackingEncodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->trackingRequestData[$paymentKey];
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtolower(md5($encodeStr));
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content
     * @return array
     */
    public function parseData($content)
    {
        /**
         * 回傳範例：
         * partner=1639|orderid=201605270000003865|payamount=0.91|opstate=2|orderno=B5025164780291325961|
         * eypaltime=20160527130226|message=success|paytype=ICBC|remark=50504_6|sign=eb645f6b70c419ee64e1bccd1a7ff288
         */
        $parseData = [];

        parse_str(str_replace('|', '&', $content), $parseData);

        return $parseData;
    }
}
