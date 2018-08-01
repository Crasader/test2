<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 幣幣支付
 */
class BBPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'order' => '', // 商戶訂單號
        'transtime' => '', // 交易時間
        'amount' => '', // 交易金額。以分為單位
        'productcategory' => '20', // 商品種類
        'productname' => '1', // 商品名稱
        'productdesc' => '1', // 商品描述
        'productprice' => '', // 商品單價
        'productcount' => '1', // 商品數量
        'merrmk' => '', // 商戶備註信息，選填
        'userua' => '', // 終端UA，選填
        'userip' => '', // 用戶IP
        'areturl' => '', // 商戶後台回調地址
        'sreturl' => '', // 商戶前台回調地址，選填
        'pnc' => '', // 支付節點編碼
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'order' => 'orderId',
        'amount' => 'amount',
        'productprice' => 'amount',
        'userip' => 'ip',
        'areturl' => 'notify_url',
        'pnc' => 'paymentVendorId',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'bborderid' => 1,
        'merid' => 1,
        'order' => 1,
        'status' => 1,
        'merrmk' => 1,
        'identityid' => 1,
        'identitytype' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '30018', // 中國工商銀行
        2 => '30016', // 交通銀行
        3 => '30022', // 中國農業銀行
        4 => '30020', // 中國建設銀行
        5 => '30001', // 招商銀行
        6 => '30004', // 中國民生銀行
        8 => '30012', // 上海浦東發展銀行
        9 => '30010', // 北京銀行
        11 => '30003', // 中信銀行
        12 => '30005', // 光大銀行
        13 => '30006', // 華夏銀行
        14 => '30014', // 廣東發展銀行
        15 => '30017', // 平安銀行
        16 => '30011', // 中國郵政儲蓄銀行
        17 => '30009', // 中國銀行
        19 => '30025', // 上海银行
        217 => '30026', // 渤海銀行
        220 => '30019', // 杭州銀行
        221 => '30023', // 浙商銀行
        226 => '30015', // 南京銀行
        234 => '30007', // 北京農村商業銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'orderid' => '', // 商戶訂單號
        'bborderid' => '', // 幣幣訂單號，選填
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'orderid' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'orderid',
        'bborderid',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'orderid' => 1,
        'merchantNo' => 1,
        'bborderid' => 1,
        'amount' => 1,
        'targetfee' => 0,
        'targetamount' => 0,
        'ordertime' => 1,
        'closetime' => 1,
        'status' => 1,
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['pnc'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['pnc'] = $this->bankMap[$this->requestData['pnc']];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);
        $this->requestData['productprice'] = round($this->requestData['amount'] * 100);
        $this->requestData['transtime'] = strtotime('now') * 1000;

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        $data = [];
        $data['merchantaccount'] = $this->options['number'];
        $data['encryptkey'] = 1;
        $data['data'] = urlencode(json_encode($this->requestData));

        return $data;
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

        if (!isset($this->options['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $this->options = json_decode(urldecode($this->options['data']), true);

        $this->payResultVerify();

        ksort($this->decodeParams);

        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 1:支付成功
        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order'] != $entry['id']) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $data = [];
        $data['merchantaccount'] = $this->options['number'];
        $data['encryptkey'] = 1;
        $data['data'] = urlencode(json_encode($this->trackingRequestData));

        $curlParam = [
            'method' => 'POST',
            'uri' => '/bbpayapi/api/query/queryOrder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($data),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        $return = json_decode($result, true);

        // 訂單查詢異常
        if (isset($return['error_code']) && isset($return['error_msg'])) {
            throw new PaymentConnectionException($return['error_msg'], 180123, $this->getEntryId());
        }

        if (!isset($return['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $returnData = json_decode(urldecode($return['data']), true);

        $this->trackingResultVerify($returnData);

        $encodeStr = '';

        ksort($this->trackingDecodeParams);

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $returnData)) {
                $encodeStr .= $returnData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($returnData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($returnData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 01處理成功、03同步，都是支付成功
        if (!in_array($returnData['status'], ['01', '03'])) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($returnData['orderid'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($returnData['amount'] != round($this->options['amount'] * 100)) {
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

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $data = [];
        $data['merchantaccount'] = $this->options['number'];
        $data['encryptkey'] = 1;
        $data['data'] = urlencode(json_encode($this->trackingRequestData));

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/bbpayapi/api/query/queryOrder',
            'method' => 'POST',
            'form' => $data,
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

        $return = json_decode($this->options['content'], true);

        // 訂單查詢異常
        if (isset($return['error_code']) && isset($return['error_msg'])) {
            throw new PaymentConnectionException($return['error_msg'], 180123, $this->getEntryId());
        }

        if (!isset($return['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $returnData = json_decode(urldecode($return['data']), true);

        $this->trackingResultVerify($returnData);

        $encodeStr = '';

        ksort($this->trackingDecodeParams);

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $returnData)) {
                $encodeStr .= $returnData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($returnData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($returnData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 01處理成功、03同步，都是支付成功
        if (!in_array($returnData['status'], ['01', '03'])) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($returnData['orderid'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($returnData['amount'] != round($this->options['amount'] * 100)) {
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

        ksort($this->requestData);

        foreach ($this->requestData as $value) {
            $encodeStr .= $value;
        }

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
        $encodeStr = '';

        ksort($this->trackingRequestData);

        foreach ($this->trackingRequestData as $value) {
            $encodeStr .= $value;
        }

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
